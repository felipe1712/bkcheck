<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PldEvento;
use App\Models\PldExpedienteVersion;
use App\Models\PldWebhookEndpoint;
use App\Models\Subject;
use App\Models\Tenant;
use App\Services\Pld\PldCryptoService;
use App\Services\Pld\PLDExpedienteFormatterService;
use App\Services\Pld\PLDScoringService;
use App\Services\Pld\PLDVersioningService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Controlador de API del Módulo de Expedientes PLD/EBR, Reconsulta, Versionado y Webhooks (M10 — Doc 2 §4).
 */
class PldModuleApiController extends Controller
{
    public function __construct(
        protected PLDVersioningService $versioning,
        protected PLDExpedienteFormatterService $formatter,
        protected PldCryptoService $crypto
    ) {}

    /**
     * 1. POST /api/personas/{id}/monitoreo
     * Alta de suscripción de monitoreo periódico (finalidad, consentimiento, intervalo).
     */
    public function subscribeMonitoring(Request $request, int $id)
    {
        $tenant = $this->resolveTenant($request);
        $subject = $this->resolveTenantSubject($tenant, $id);

        $validated = $request->validate([
            'finalidad'          => 'nullable|string|in:alta_cliente,seleccion_personal,otro',
            'consentimiento_ref' => 'required|string|max:190',
            'intervalo_meses'    => 'nullable|integer|min:1|max:6',
            'ejecutar_inicial'   => 'nullable|boolean',
            'fuentes'            => 'nullable|array',
        ]);

        $suscripcion = $this->versioning->createOrUpdateSubscription($subject, $validated);

        $version = null;
        if ($request->boolean('ejecutar_inicial', false)) {
            $version = $this->versioning->generateVersion(
                $subject,
                'INICIAL',
                $validated['fuentes'] ?? null
            );
        }

        return response()->json([
            'suscripcion_id'       => $suscripcion->suscripcion_id,
            'persona_id'           => $subject->id,
            'finalidad'            => $suscripcion->finalidad,
            'consentimiento_ref'   => $suscripcion->consentimiento_ref,
            'intervalo_meses'      => $suscripcion->intervalo_meses,
            'estado'               => $suscripcion->estado,
            'proxima_reevaluacion' => $suscripcion->proxima_reevaluacion?->toIso8601String(),
            'folio_generado'       => $version?->folio,
        ], 201);
    }

    /**
     * 2. DELETE /api/personas/{id}/monitoreo
     * Baja del monitoreo (`relacion_terminada` o `retiro_consentimiento`).
     */
    public function cancelMonitoring(Request $request, int $id)
    {
        $tenant = $this->resolveTenant($request);
        $subject = $this->resolveTenantSubject($tenant, $id);

        $motivo = (string) $request->input('motivo', 'relacion_terminada');
        $suscripcion = $this->versioning->cancelSubscription($subject, $motivo);

        return response()->json([
            'suscripcion_id'         => $suscripcion->suscripcion_id,
            'persona_id'             => $subject->id,
            'estado'                 => $suscripcion->estado,
            'motivo_baja'            => $suscripcion->motivo_baja,
            'consentimiento_vigente' => $suscripcion->consentimiento_vigente,
            'proxima_reevaluacion'   => null,
        ]);
    }

    /**
     * POST /api/personas/{id}/expedientes
     * Ejecuta una evaluación / reevaluación bajo demanda o inicial para la persona.
     */
    public function generateExpediente(Request $request, int $id)
    {
        $tenant = $this->resolveTenant($request);
        $subject = $this->resolveTenantSubject($tenant, $id);

        $validated = $request->validate([
            'tipo_ejecucion'   => 'nullable|string|in:INICIAL,REEVALUACION_PERIODICA,REEVALUACION_ANUAL_IDENTIDAD,REEVALUACION_POR_EVENTO,BAJO_DEMANDA,CORRECCION',
            'folio_corregido'  => 'nullable|string|max:64',
            'fuentes'          => 'nullable|array',
            'fecha_generacion' => 'nullable|string',
        ]);

        try {
            $version = $this->versioning->generateVersion(
                $subject,
                $validated['tipo_ejecucion'] ?? 'BAJO_DEMANDA',
                $validated['fuentes'] ?? null,
                $validated['folio_corregido'] ?? null,
                $validated['fecha_generacion'] ?? null
            );
        } catch (\DomainException $e) {
            return response()->json([
                'error'   => 'Regla de negocio / Consentimiento / Integridad PLD',
                'message' => $e->getMessage(),
            ], 422);
        }

        $payload = json_decode($version->payload_canonico, true);

        return response()->json($payload, 201);
    }

    /**
     * 3. GET /api/personas/{id}/expedientes
     * Lista de versiones inmutables de una persona (folio, versión, fecha, nivel, recomendación, hash, hash_previo).
     */
    public function listExpedientes(Request $request, int $id)
    {
        $tenant = $this->resolveTenant($request);
        $subject = $this->resolveTenantSubject($tenant, $id);

        $versions = PldExpedienteVersion::where('tenant_id', $tenant->id)
            ->where('subject_id', $subject->id)
            ->orderBy('version')
            ->get()
            ->map(function (PldExpedienteVersion $v) {
                $decoded = json_decode($v->payload_canonico, true) ?: [];
                return [
                    'folio'                => $v->folio,
                    'version'              => $v->version,
                    'tipo_ejecucion'       => $v->tipo_ejecucion,
                    'fecha_generacion'     => $v->fecha_generacion?->toIso8601String(),
                    'nivel_riesgo_persona' => $decoded['nivel_riesgo_persona']['nivel'] ?? 'MEDIO',
                    'recomendacion'        => $decoded['recomendacion'] ?? 'REVISION_MANUAL',
                    'score_global'         => $decoded['score_global'] ?? 0,
                    'hash_version'         => $v->hash_version,
                    'hash_previo'          => $v->hash_previo,
                    'cambios'              => $v->cambios_resumen['cambios'] ?? null,
                ];
            });

        return response()->json([
            'persona_id' => $subject->id,
            'total'      => $versions->count(),
            'versiones'  => $versions,
        ]);
    }

    /**
     * 4. GET /api/expedientes/{folio}
     * Devuelve el expediente completo en JSON estable (Doc 2 §5) o PDF (`?format=pdf`).
     */
    public function showExpediente(Request $request, string $folio)
    {
        $tenant = $this->resolveTenant($request);

        $version = PldExpedienteVersion::where('tenant_id', $tenant->id)
            ->where('folio', $folio)
            ->firstOrFail();

        $subject = Subject::withoutGlobalScopes()->find($version->subject_id);
        $this->logExpedienteAccess($tenant, $subject, $folio);

        $expediente = json_decode($version->payload_canonico, true);

        // Validar que ninguna salida contenga referencias a avisos o frases prohibidas
        PLDScoringService::assertNoForbiddenPhrases($version->payload_canonico);

        if ($request->query('format') === 'pdf' || str_contains((string) $request->header('Accept'), 'application/pdf')) {
            $pdf = Pdf::loadView('tenant.reports.pld_expediente_pdf', [
                'exp'           => $expediente,
                'formattedText' => $version->expediente_formateado_texto,
            ])->setPaper('letter', 'portrait');

            return $pdf->download("Expediente_PLD_{$folio}.pdf");
        }

        if ($request->query('format') === 'text') {
            return response($version->expediente_formateado_texto, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        return response()->json($expediente);
    }

    /**
     * 5. GET /api/expedientes/{folio}/diff?contra={folio}
     * Devuelve el diff estructurado entre dos versiones del expediente.
     */
    public function diffExpediente(Request $request, string $folio)
    {
        $tenant = $this->resolveTenant($request);

        $actual = PldExpedienteVersion::where('tenant_id', $tenant->id)
            ->where('folio', $folio)
            ->firstOrFail();

        $contraFolio = $request->query('contra');
        if ($contraFolio) {
            PldExpedienteVersion::where('tenant_id', $tenant->id)
                ->where('folio', $contraFolio)
                ->firstOrFail();
        }

        $diff = $this->versioning->diffBetweenFolios($actual->folio, $contraFolio);

        return response()->json($diff);
    }

    /**
     * 6. POST /api/expedientes/verificar
     * Recibe un JSON de expediente y verifica su hash canónico JCS (RFC 8785).
     */
    public function verifyExpediente(Request $request)
    {
        $payload = $request->input('expediente') ?? $request->all();
        if (!is_array($payload) || empty($payload)) {
            return response()->json([
                'valido'  => false,
                'message' => 'Cuerpo JSON de expediente vacío o inválido.',
            ], 422);
        }

        $result = $this->versioning->verifyExpedientePayload($payload);

        return response()->json($result, $result['valido'] ? 200 : 422);
    }

    /**
     * 7. POST /api/webhooks
     * Configura URL, secreto y eventos suscritos para el tenant.
     */
    public function configureWebhook(Request $request)
    {
        $tenant = $this->resolveTenant($request);

        $validated = $request->validate([
            'url'                 => 'required|url|max:500',
            'secreto'             => 'required|string|min:16|max:255',
            'eventos_suscritos'   => 'required|array|min:1',
            'eventos_suscritos.*' => 'string|in:*,NIVEL_CAMBIO,HALLAZGO_CRITICO,RECOMENDACION_CAMBIO,REEVALUACION_VENCIDA,FUENTE_CAIDA_PERSISTENTE,REEVALUACION_SIN_CAMBIOS',
            'incluir_sin_cambios' => 'nullable|boolean',
        ]);

        $endpoint = PldWebhookEndpoint::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'url'       => $validated['url'],
            ],
            [
                'secreto'             => $validated['secreto'],
                'eventos_suscritos'   => $validated['eventos_suscritos'],
                'incluir_sin_cambios' => (bool) ($validated['incluir_sin_cambios'] ?? false),
                'activo'              => true,
            ]
        );

        return response()->json([
            'webhook_id'          => $endpoint->id,
            'url'                 => $endpoint->url,
            'eventos_suscritos'   => $endpoint->eventos_suscritos,
            'incluir_sin_cambios' => $endpoint->incluir_sin_cambios,
            'activo'              => $endpoint->activo,
        ], 201);
    }

    /**
     * 8. GET /api/eventos?desde=
     * Devuelve eventos pendientes o emitidos desde una marca de tiempo ISO 8601.
     */
    public function listEventos(Request $request)
    {
        $tenant = $this->resolveTenant($request);

        $query = PldEvento::where('tenant_id', $tenant->id)->orderBy('fecha');

        if ($request->filled('desde')) {
            $desde = Carbon::parse((string) $request->query('desde'));
            $query->where('fecha', '>=', $desde);
        }

        if ($request->boolean('solo_no_entregados', false)) {
            $query->whereNull('entregado_en');
        }

        $eventos = $query->get()->map(fn (PldEvento $e) => [
            'evento_id'       => $e->evento_id,
            'tipo'            => $e->tipo,
            'folio_origen'    => $e->folio_origen,
            'fecha'           => $e->fecha?->toIso8601String(),
            'entregado_en'    => $e->entregado_en?->toIso8601String(),
            'intentos'        => $e->intentos,
            'payload_webhook' => $e->payload_webhook,
        ]);

        return response()->json([
            'total'   => $eventos->count(),
            'eventos' => $eventos,
        ]);
    }

    protected function resolveTenant(Request $request): Tenant
    {
        $tenant = $request->attributes->get('tenant');
        if ($tenant instanceof Tenant) {
            return $tenant;
        }
        abort(401, 'Tenant no identificado.');
    }

    protected function resolveTenantSubject(Tenant $tenant, int $id): Subject
    {
        $subject = Subject::withoutGlobalScopes()
            ->where('id', $id)
            ->where(function ($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id)
                  ->orWhereHas('project', fn ($pq) => $pq->withoutGlobalScopes()->where('tenant_id', $tenant->id));
            })
            ->first();

        if (!$subject) {
            abort(404, "Persona #{$id} no encontrada para este tenant.");
        }

        return $subject;
    }

    protected function logExpedienteAccess(Tenant $tenant, ?Subject $subject, string $folio): void
    {
        try {
            $userId = auth()->id() ?? $tenant->users()->first()?->id ?? 1;
            AuditLog::create([
                'tenant_id'    => $tenant->id,
                'user_id'      => $userId,
                'subject_name' => $subject?->name_or_company ?? 'Sujeto PLD',
                'subject_rfc'  => $subject?->rfc ?? 'SIN_RFC',
                'fuente'       => "Consulta Expediente PLD ({$folio})",
                'ip_address'   => request()->ip() ?? '127.0.0.1',
                'created_at'   => now(),
            ]);
        } catch (\Throwable) {
            // No interrumpir respuesta de API si no existe usuario asociado en entorno de test
        }
    }
}
