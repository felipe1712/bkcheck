<?php

namespace App\Services\Pld;

use App\Models\PldEvento;
use App\Models\PldWebhookEndpoint;
use App\Models\Subject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Servicio de Emisión y Entrega Segura de Webhooks PLD (M9 — Doc 2 §3).
 *
 * Garantías:
 * - Cero datos personales (sin nombre, RFC, CURP ni domicilio) y cero detalle del hallazgo en el payload.
 * - Cero mención a avisos o requerimientos del sujeto obligado (Ley Art. 63 fr. II).
 * - Firma HMAC-SHA256 por tenant con timestamp (ventana anti-replay de 5 minutos).
 * - Idempotencia por `evento_id` (reintentar un evento no duplica efectos ni genera nuevo ID).
 * - Si se agotan los reintentos o no hay endpoint activo, el evento queda disponible en `GET /api/eventos?desde=`.
 */
class PLDWebhookDispatcherService
{
    public function __construct(
        protected PldCryptoService $crypto
    ) {}

    /**
     * Evalúa qué eventos deben emitirse tras la creación de una versión de expediente y los registra/despacha.
     *
     * @param Subject $subject
     * @param array $currentExp Datos estructurados de la versión actual v{n}
     * @param array|null $prevExp Datos estructurados de la versión previa v{n-1} (null si es v1)
     * @param string|null $suscripcionId UUID de la suscripción si existe
     * @param array $persistentFallenSources Lista de fuentes que alcanzaron 3 fallos consecutivos
     * @return array<PldEvento>
     */
    public function evaluateAndEmitVersionEvents(
        Subject $subject,
        array $currentExp,
        ?array $prevExp = null,
        ?string $suscripcionId = null,
        array $persistentFallenSources = []
    ): array {
        $emitted = [];
        $folio = (string) ($currentExp['folio'] ?? '');
        $nivelActual = (string) ($currentExp['nivel_riesgo_persona']['nivel'] ?? 'MEDIO');
        $nivelPrevio = $prevExp ? (string) ($prevExp['nivel_riesgo_persona']['nivel'] ?? $nivelActual) : null;

        // 1. Detectar HALLAZGO_CRITICO nuevo (PEP, sanción, 69-B, coincidencia en lista PLD)
        if ($this->hasNewCriticalPldFinding($currentExp, $prevExp)) {
            $emitted[] = $this->createAndDispatchEvent(
                $subject,
                'HALLAZGO_CRITICO',
                $folio,
                $nivelPrevio,
                $nivelActual,
                $suscripcionId
            );
        }

        // 2. Detectar NIVEL_CAMBIO (en reevaluaciones)
        if ($prevExp !== null && $nivelPrevio !== $nivelActual) {
            $emitted[] = $this->createAndDispatchEvent(
                $subject,
                'NIVEL_CAMBIO',
                $folio,
                $nivelPrevio,
                $nivelActual,
                $suscripcionId
            );
        }

        // 3. Detectar RECOMENDACION_CAMBIO (en reevaluaciones)
        $recActual = (string) ($currentExp['recomendacion'] ?? '');
        $recPrevia = $prevExp ? (string) ($prevExp['recomendacion'] ?? '') : null;
        if ($prevExp !== null && $recPrevia !== $recActual) {
            $emitted[] = $this->createAndDispatchEvent(
                $subject,
                'RECOMENDACION_CAMBIO',
                $folio,
                $nivelPrevio,
                $nivelActual,
                $suscripcionId
            );
        }

        // 4. Detectar FUENTE_CAIDA_PERSISTENTE (3 ejecuciones seguidas con fallo)
        if (!empty($persistentFallenSources)) {
            $emitted[] = $this->createAndDispatchEvent(
                $subject,
                'FUENTE_CAIDA_PERSISTENTE',
                $folio,
                $nivelPrevio,
                $nivelActual,
                $suscripcionId
            );
        }

        // 5. Detectar REEVALUACION_SIN_CAMBIOS (cuando es reevaluación y cambios.cambios === false)
        $huboCambios = (bool) ($currentExp['cambios']['cambios'] ?? true);
        if ($prevExp !== null && !$huboCambios && empty($emitted)) {
            $emitted[] = $this->createAndDispatchEvent(
                $subject,
                'REEVALUACION_SIN_CAMBIOS',
                $folio,
                $nivelPrevio,
                $nivelActual,
                $suscripcionId
            );
        }

        return $emitted;
    }

    /**
     * Emite un evento de REEVALUACION_VENCIDA cuando pasó proxima_reevaluacion sin ejecución.
     */
    public function emitOverdueReevaluationEvent(
        Subject $subject,
        string $folioVigente,
        string $nivelActual,
        ?string $suscripcionId = null
    ): PldEvento {
        return $this->createAndDispatchEvent(
            $subject,
            'REEVALUACION_VENCIDA',
            $folioVigente,
            $nivelActual,
            $nivelActual,
            $suscripcionId
        );
    }

    /**
     * Crea el registro inmutable del evento con payload mínimo libre de PII y lo intenta entregar.
     */
    public function createAndDispatchEvent(
        Subject $subject,
        string $tipo,
        string $folio,
        ?string $nivelPrevio,
        string $nivelActual,
        ?string $suscripcionId = null
    ): PldEvento {
        $tenantId = (int) ($subject->tenant_id ?: $subject->project?->tenant_id ?: 1);
        $eventoId = 'evt_' . Str::lower(Str::ulid()->toBase32());
        $fechaIso = now()->toIso8601String();

        // Referencia opaca al sujeto (NUNCA nombre, RFC ni CURP en el webhook — Doc 2 §3.2)
        $personaRef = 'ref_' . substr(hash_hmac('sha256', 'subj:' . $subject->id, (string) config('app.key')), 0, 16);

        $payload = [
            'evento_id'      => $eventoId,
            'tipo'           => $tipo,
            'folio'          => $folio,
            'persona_ref'    => $personaRef,
            'nivel_previo'   => $nivelPrevio,
            'nivel_actual'   => $nivelActual,
            'fecha'          => $fechaIso,
            'url_expediente' => url("/api/expedientes/{$folio}"),
        ];

        // Validación estricta anti-avisos / anti-frases prohibidas (Ley Art. 63 fr. II)
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        PLDScoringService::assertNoForbiddenPhrases($jsonPayload);

        $evento = PldEvento::create([
            'evento_id'       => $eventoId,
            'tenant_id'       => $tenantId,
            'suscripcion_id'  => $suscripcionId,
            'subject_id'      => $subject->id,
            'tipo'            => $tipo,
            'folio_origen'    => $folio,
            'payload_webhook' => $payload,
            'fecha'           => now(),
            'entregado_en'    => null,
            'intentos'        => 0,
        ]);

        $this->deliverEvent($evento);

        return $evento->fresh();
    }

    /**
     * Entrega o reintenta un evento hacia los endpoints suscritos del tenant.
     * Garantiza idempotencia: si el evento ya tiene `entregado_en`, no vuelve a enviarse.
     */
    public function deliverEvent(PldEvento $evento): bool
    {
        if ($evento->entregado_en !== null) {
            // Idempotencia: ya fue entregado con éxito previamente
            return true;
        }

        $endpoints = PldWebhookEndpoint::where('tenant_id', $evento->tenant_id)
            ->where('activo', true)
            ->get();

        if ($endpoints->isEmpty()) {
            return false;
        }

        $payloadArray = $evento->payload_webhook;
        $jsonBody = json_encode($payloadArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        PLDScoringService::assertNoForbiddenPhrases($jsonBody);

        $matchingEndpoints = $endpoints->filter(function (PldWebhookEndpoint $ep) use ($evento) {
            if ($evento->tipo === 'REEVALUACION_SIN_CAMBIOS' && !$ep->incluir_sin_cambios) {
                return false;
            }
            $suscritos = is_array($ep->eventos_suscritos) ? $ep->eventos_suscritos : [];
            return in_array('*', $suscritos, true) || in_array($evento->tipo, $suscritos, true);
        });

        if ($matchingEndpoints->isEmpty()) {
            return false;
        }

        $evento->intentos = (int) $evento->intentos + 1;
        $allDelivered = true;
        $lastError = null;

        foreach ($matchingEndpoints as $ep) {
            $secret = $ep->decrypted_secreto;
            $timestamp = time();
            $signed = $this->crypto->signWebhookPayload($jsonBody, $secret, $timestamp);

            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Content-Type'       => 'application/json',
                        'X-AvalID-Event-Id'  => $evento->evento_id,
                        'X-AvalID-Timestamp' => (string) $signed['timestamp'],
                        'X-AvalID-Signature' => 'sha256=' . $signed['signature'],
                    ])
                    ->withBody($jsonBody, 'application/json')
                    ->post($ep->url);

                if (!$response->successful()) {
                    $allDelivered = false;
                    $lastError = "HTTP {$response->status()} desde {$ep->url}";
                }
            } catch (\Throwable $e) {
                $allDelivered = false;
                $lastError = $e->getMessage();
            }
        }

        if ($allDelivered) {
            $evento->entregado_en = now();
            $evento->ultimo_error = null;
        } else {
            $evento->ultimo_error = $lastError;
        }

        $evento->save();

        return $allDelivered;
    }

    /**
     * Determina si apareció un hallazgo crítico nuevo (PEP, sanción, 69-B, lista PLD) en la versión actual.
     */
    protected function hasNewCriticalPldFinding(array $currentExp, ?array $prevExp): bool
    {
        $currPep = ($currentExp['pep']['estatus'] ?? '') === 'REGISTRADO COMO PEP';
        $prevPep = $prevExp ? (($prevExp['pep']['estatus'] ?? '') === 'REGISTRADO COMO PEP') : false;

        if ($currPep && !$prevPep) {
            return true;
        }

        $currPldHoldings = $this->extractCriticalPldKeys($currentExp['hallazgos'] ?? []);
        $prevPldHoldings = $prevExp ? $this->extractCriticalPldKeys($prevExp['hallazgos'] ?? []) : [];

        $newCritical = array_diff($currPldHoldings, $prevPldHoldings);
        return !empty($newCritical);
    }

    protected function extractCriticalPldKeys(array $hallazgos): array
    {
        $keys = [];
        foreach ($hallazgos as $h) {
            if (($h['categoria'] ?? '') === 'PLD' || ($h['severidad'] ?? '') === 'CRITICA') {
                $keys[] = ($h['trigger_key'] ?? '') . ':' . ($h['fuente'] ?? '') . ':' . ($h['dato_respaldo'] ?? '');
            }
        }
        return array_unique($keys);
    }
}
