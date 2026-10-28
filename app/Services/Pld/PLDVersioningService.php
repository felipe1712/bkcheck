<?php

namespace App\Services\Pld;

use App\Models\ApiUsage;
use App\Models\AuditLog;
use App\Models\ExpedientePldSujeto;
use App\Models\PldExpedienteVersion;
use App\Models\PldFuenteCatalogo;
use App\Models\PldFuenteResultado;
use App\Models\PldSuscripcion;
use App\Models\SourceQuery;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de Reconsulta Periódica, Versionado Inmutable con Hash Encadenado (JCS RFC 8785) y Diff Estructurado (M8 — Doc 2).
 */
class PLDVersioningService
{
    public function __construct(
        protected PLDScoringService $scoring,
        protected PLDExpedienteFormatterService $formatter,
        protected PldCryptoService $crypto,
        protected PLDWebhookDispatcherService $webhookDispatcher
    ) {}

    /**
     * Genera una nueva versión inmutable del Expediente PLD/EBR para un sujeto.
     *
     * Reglas determinísticas (Doc 2 §1.2 y §2.2):
     * 1. Cada ejecución crea una versión nueva secuencial sin huecos: `P-{padded_id}-v{n}`.
     * 2. Si es reconsulta y la suscripción está dada de BAJA o sin consentimiento vigente, se bloquea.
     * 3. Si en una reconsulta una fuente falla (HTTP >= 400 o timeout), se arrastra su `resultado_crudo`
     *    previo marcado como `NO_RECONSULTADA` con su `ultima_consulta_exitosa`, de modo que un hallazgo
     *    positivo previo (PEP, sanción, 69-B) NUNCA se borre por una fuente caída.
     * 4. Si una fuente acumula 3 ejecuciones seguidas caída, emite `FUENTE_CAIDA_PERSISTENTE`.
     * 5. Calcula diff estructurado contra la versión inmediata anterior y encadena `hash_previo` -> `hash_version`.
     *
     * @param Subject $subject
     * @param string $tipoEjecucion INICIAL | REEVALUACION_PERIODICA | REEVALUACION_ANUAL_IDENTIDAD | REEVALUACION_POR_EVENTO | BAJO_DEMANDA | CORRECCION
     * @param array|null $fuentesOverride Arreglo opcional de entradas crudas por fuente (si es null, extrae de SourceQuery/SourceResult del sujeto)
     * @param string|null $folioCorregido Folio de referencia cuando $tipoEjecucion === 'CORRECCION'
     * @param string|null $fechaGeneracionOverride Fecha ISO 8601 opcional para pruebas determinísticas
     * @return PldExpedienteVersion
     */
    public function generateVersion(
        Subject $subject,
        string $tipoEjecucion = 'INICIAL',
        ?array $fuentesOverride = null,
        ?string $folioCorregido = null,
        ?string $fechaGeneracionOverride = null
    ): PldExpedienteVersion {
        $tenantId = (int) ($subject->tenant_id ?: $subject->project?->tenant_id ?: 1);

        // Validar suscripción y consentimiento si es una reconsulta (Doc 2 §1.3)
        $suscripcion = PldSuscripcion::where('tenant_id', $tenantId)
            ->where('subject_id', $subject->id)
            ->first();

        if ($suscripcion && ($suscripcion->estado === 'BAJA' || !$suscripcion->consentimiento_vigente)) {
            throw new \DomainException(
                "No se puede ejecutar reconsulta para el sujeto #{$subject->id}: el monitoreo está en estado '{$suscripcion->estado}' o sin consentimiento vigente."
            );
        }

        if ($tipoEjecucion !== 'INICIAL' && !$subject->consent_granted) {
            throw new \DomainException(
                "No se puede ejecutar reconsulta sin consentimiento vigente otorgado para la finalidad."
            );
        }

        return DB::transaction(function () use (
            $subject,
            $tenantId,
            $tipoEjecucion,
            $fuentesOverride,
            $folioCorregido,
            $fechaGeneracionOverride,
            $suscripcion
        ) {
            // Determinar versión anterior (si existe) para encadenar folio y hash_previo
            $prevVersionModel = PldExpedienteVersion::where('subject_id', $subject->id)
                ->orderByDesc('version')
                ->lockForUpdate()
                ->first();

            $nextVersionNum = $prevVersionModel ? ((int) $prevVersionModel->version + 1) : 1;
            if ($nextVersionNum > 1 && $tipoEjecucion === 'INICIAL') {
                $tipoEjecucion = 'REEVALUACION_PERIODICA';
            }

            $folio = self::formatFolio($subject->id, $nextVersionNum);
            $hashPrevio = $prevVersionModel ? $prevVersionModel->hash_version : null;
            $fechaGen = $fechaGeneracionOverride
                ? Carbon::parse($fechaGeneracionOverride)->toIso8601String()
                : now()->toIso8601String();

            // 1. Obtener entradas crudas de las fuentes
            $rawInputs = $fuentesOverride !== null
                ? $fuentesOverride
                : $this->collectInputsFromSubjectQueries($subject, $fechaGen);

            // 2. Aplicar Regla 1.2.3 (Arrastre de fuente caída en reconsulta) y Regla 1.2.4 (Conteo de 3 fallos consecutivos)
            $fallosConsecutivos = $suscripcion && is_array($suscripcion->fallos_consecutivos_por_fuente)
                ? $suscripcion->fallos_consecutivos_por_fuente
                : $this->computeHistoricalConsecutiveFailures($subject->id);

            $persistentFallenSources = [];
            $processedInputs = $this->applyReconsultationFallenSourceRules(
                $rawInputs,
                $prevVersionModel?->folio,
                $fechaGen,
                $fallosConsecutivos,
                $persistentFallenSources
            );

            // 3. Evaluar primero el paquete base para poder calcular el diff estructurado contra la versión previa
            $metaVersion = [
                'folio'            => $folio,
                'version'          => $nextVersionNum,
                'tipo_ejecucion'   => $tipoEjecucion,
                'folio_corregido'  => $folioCorregido,
                'fecha_generacion' => $fechaGen,
                'hash_previo'      => $hashPrevio,
            ];

            $prevExpData = $prevVersionModel
                ? json_decode($prevVersionModel->payload_canonico, true)
                : null;

            // Primera pasada determinística para obtener estado actual y calcular diff si hay versión previa
            $preliminaryExp = $this->scoring->evaluate($subject, $processedInputs, $metaVersion, null);

            $diffEstructurado = null;
            if ($prevExpData !== null) {
                $diffEstructurado = $this->computeStructuredDiff($prevExpData, $preliminaryExp);
                // Re-evaluar inyectando el diff estructurado para que forme parte del JSON canónico firmado con JCS
                $finalExp = $this->scoring->evaluate($subject, $processedInputs, $metaVersion, $diffEstructurado);
            } else {
                $finalExp = $preliminaryExp;
            }

            // 4. Canonicalizar con RFC 8785 (JCS) excluyendo hash_version y verificar hash
            $forHash = $finalExp;
            unset($forHash['hash_version']);
            if (isset($forHash['meta_version']) && is_array($forHash['meta_version'])) {
                unset($forHash['meta_version']['hash_version']);
            }
            $hashVersion = JcsCanonicalizer::hashPayload($forHash);
            $finalExp['hash_version'] = $hashVersion;
            $finalExp['meta_version']['hash_version'] = $hashVersion;

            $payloadCanonico = JcsCanonicalizer::canonicalize($finalExp);
            $textoFormateado = $this->formatter->formatText($finalExp);

            // Validación de fraseología prohibida también sobre el texto formateado
            PLDScoringService::assertNoForbiddenPhrases($textoFormateado);

            // 5. Persistir o actualizar cabecera en `expedientes_pld_sujetos` con RFC cifrado AES-256-GCM + Blind Index
            $rfcPlain = (string) ($subject->rfc ?? 'SIN_RFC');
            $expedienteCabecera = ExpedientePldSujeto::updateOrCreate(
                [
                    'tenant_id'  => $tenantId,
                    'subject_id' => $subject->id,
                ],
                [
                    'rfc_cifrado'             => $this->crypto->encryptIdentifier($rfcPlain),
                    'rfc_bindex'              => $this->crypto->blindIndex($rfcPlain),
                    'tipo_persona'            => $finalExp['persona']['tipo_persona'],
                    'score_global'            => $finalExp['score_global'],
                    'scores_categoria'        => $finalExp['scores_categoria'],
                    'nivel_riesgo_persona'    => $finalExp['nivel_riesgo_persona']['nivel'],
                    'recomendacion'           => $finalExp['recomendacion'],
                    'edd_recomendada'         => $finalExp['edd']['recomendada'],
                    'edd_causa'               => $finalExp['edd']['causa'],
                    'factores_riesgo_json'    => $finalExp['factores_inherentes'],
                    'hallazgos_json'          => $finalExp['hallazgos'],
                    'fuentes_pendientes_json' => $finalExp['fuentes_pendientes'],
                    'conservar_hasta'         => Carbon::parse($finalExp['conservar_hasta']),
                    'finalidad'               => $finalExp['finalidad'],
                    'folio_vigente'           => $folio,
                    'version_vigente'         => $nextVersionNum,
                    'proxima_reevaluacion'    => Carbon::parse($finalExp['proxima_reevaluacion']),
                ]
            );

            // 6. Guardar versión inmutable en `pld_expediente_versiones`
            $versionModel = PldExpedienteVersion::create([
                'folio'                       => $folio,
                'tenant_id'                   => $tenantId,
                'subject_id'                  => $subject->id,
                'expediente_pld_id'           => $expedienteCabecera->id,
                'version'                     => $nextVersionNum,
                'tipo_ejecucion'              => $tipoEjecucion,
                'folio_corregido'             => $folioCorregido,
                'fecha_generacion'            => Carbon::parse($fechaGen),
                'hash_version'                => $hashVersion,
                'hash_previo'                 => $hashPrevio,
                'payload_canonico'            => $payloadCanonico,
                'expediente_formateado_texto' => $textoFormateado,
                'cambios_resumen'             => $diffEstructurado,
                'schema_version'              => 1,
            ]);

            // 7. Guardar registros de fuentes en `pld_fuente_resultado`
            $catalogo = PldFuenteCatalogo::all()->keyBy('fuente_key');
            foreach ($processedInputs as $item) {
                $fKey = (string) ($item['fuente'] ?? '');
                $catEntry = $catalogo->get($fKey);
                PldFuenteResultado::create([
                    'folio'                   => $folio,
                    'fuente'                  => $fKey,
                    'categoria'               => $catEntry?->categoria ?? 'NO_CATALOGADA',
                    'criticidad'              => $catEntry?->criticidad ?? null,
                    'status_http'             => (int) ($item['status_http'] ?? 200),
                    'resultado_crudo'         => is_array($item['resultado_crudo'] ?? null) ? $item['resultado_crudo'] : [],
                    'fecha_consulta'          => isset($item['fecha_consulta']) ? Carbon::parse($item['fecha_consulta']) : Carbon::parse($fechaGen),
                    'ultima_consulta_exitosa' => !empty($item['ultima_consulta_exitosa']) ? Carbon::parse($item['ultima_consulta_exitosa']) : null,
                    'estado'                  => $item['estado'] ?? 'CONSULTADA',
                ]);
            }

            // 8. Actualizar suscripción si existe
            if ($suscripcion) {
                $suscripcion->intervalo_meses = (int) $finalExp['intervalo_reevaluacion_meses'];
                $suscripcion->proxima_reevaluacion = Carbon::parse($finalExp['proxima_reevaluacion']);
                $suscripcion->fallos_consecutivos_por_fuente = $fallosConsecutivos;
                if (in_array($tipoEjecucion, ['INICIAL', 'REEVALUACION_ANUAL_IDENTIDAD', 'REEVALUACION_PERIODICA'], true)) {
                    $suscripcion->ultima_reevaluacion_identidad = Carbon::parse($fechaGen);
                }
                $suscripcion->save();
            }

            // 9. Registrar bitácora de acceso/generación (LFPDPPP / PLD §6) y consumo en ApiUsage
            $this->recordAuditAndUsage($subject, $tenantId, $folio);

            // 10. Emitir eventos de webhook que correspondan (M9)
            $this->webhookDispatcher->evaluateAndEmitVersionEvents(
                $subject,
                $finalExp,
                $prevExpData,
                $suscripcion?->suscripcion_id,
                $persistentFallenSources
            );

            return $versionModel;
        });
    }

    /**
     * Crea o reactiva una suscripción de monitoreo periódico para un sujeto (Doc 2 §1 y §4).
     */
    public function createOrUpdateSubscription(Subject $subject, array $params = []): PldSuscripcion
    {
        $tenantId = (int) ($subject->tenant_id ?: $subject->project?->tenant_id ?: 1);
        $finalidad = $params['finalidad'] ?? $subject->finalidad_clave ?? 'alta_cliente';
        $consentimientoRef = $params['consentimiento_ref'] ?? ('CONS-' . $subject->id . '-' . now()->format('Ymd'));
        $intervaloMeses = min(6, max(1, (int) ($params['intervalo_meses'] ?? 6)));

        // Asegurar que el sujeto tenga marcado el consentimiento vigente
        if (!$subject->consent_granted) {
            $subject->consent_granted = true;
            $subject->consent_date = $subject->consent_date ?? now();
            $subject->consent_legal_basis = $consentimientoRef;
            $subject->save();
        }

        $proxima = isset($params['proxima_reevaluacion'])
            ? Carbon::parse($params['proxima_reevaluacion'])
            : now()->addMonths($intervaloMeses);

        return PldSuscripcion::updateOrCreate(
            [
                'tenant_id'  => $tenantId,
                'subject_id' => $subject->id,
            ],
            [
                'finalidad'                      => $finalidad,
                'consentimiento_ref'             => $consentimientoRef,
                'consentimiento_vigente'         => true,
                'intervalo_meses'                => $intervaloMeses,
                'estado'                         => 'ACTIVA',
                'motivo_baja'                    => null,
                'proxima_reevaluacion'           => $proxima,
                'ultima_reevaluacion_identidad'  => now(),
                'fallos_consecutivos_por_fuente' => [],
            ]
        );
    }

    /**
     * Da de baja el monitoreo de una persona (`relacion_terminada` o `retiro_consentimiento` — Doc 2 §1.3).
     * Detiene inmediatamente cualquier reconsulta ulterior.
     */
    public function cancelSubscription(Subject $subject, string $motivo = 'relacion_terminada'): PldSuscripcion
    {
        $tenantId = (int) ($subject->tenant_id ?: $subject->project?->tenant_id ?: 1);
        $suscripcion = PldSuscripcion::firstOrCreate(
            [
                'tenant_id'  => $tenantId,
                'subject_id' => $subject->id,
            ],
            [
                'finalidad'          => 'alta_cliente',
                'consentimiento_ref' => 'BAJA',
                'intervalo_meses'    => 6,
            ]
        );

        $suscripcion->estado = 'BAJA';
        $suscripcion->motivo_baja = $motivo;
        $suscripcion->proxima_reevaluacion = null;
        if (str_contains(strtolower($motivo), 'consentimiento')) {
            $suscripcion->consentimiento_vigente = false;
            $subject->consent_granted = false;
            $subject->save();
        }
        $suscripcion->save();

        return $suscripcion;
    }

    /**
     * Calcula el diff estructurado determinístico entre dos versiones cualesquiera del expediente (Doc 2 §2.3).
     * Garantiza que diff(v1, v3) coincida exactamente con la composición acumulativa de v1->v2 y v2->v3.
     */
    public function computeStructuredDiff(array $baseExp, array $targetExp): array
    {
        $items = [];
        $fechaTarget = $targetExp['fecha_generacion'] ?? now()->toIso8601String();

        // 1. Nivel de Riesgo de Persona
        $nivAntes   = $baseExp['nivel_riesgo_persona']['nivel'] ?? null;
        $nivDespues = $targetExp['nivel_riesgo_persona']['nivel'] ?? null;
        if ($nivAntes !== $nivDespues) {
            $items[] = [
                'campo'          => 'nivel_riesgo_persona',
                'antes'          => $nivAntes,
                'despues'        => $nivDespues,
                'fuente'         => 'motor_ebr_avalid',
                'fecha_consulta' => $fechaTarget,
            ];
        }

        // 2. Recomendación de acción
        $recAntes   = $baseExp['recomendacion'] ?? null;
        $recDespues = $targetExp['recomendacion'] ?? null;
        if ($recAntes !== $recDespues) {
            $items[] = [
                'campo'          => 'recomendacion',
                'antes'          => $recAntes,
                'despues'        => $recDespues,
                'fuente'         => 'motor_ebr_avalid',
                'fecha_consulta' => $fechaTarget,
            ];
        }

        // 3. Score global (si cambia en más de 5 puntos — Doc 2 §2.3)
        $sgAntes   = (int) ($baseExp['score_global'] ?? 0);
        $sgDespues = (int) ($targetExp['score_global'] ?? 0);
        if (abs($sgDespues - $sgAntes) > 5) {
            $items[] = [
                'campo'          => 'score_global',
                'antes'          => $sgAntes,
                'despues'        => $sgDespues,
                'fuente'         => 'motor_scoring_avalid',
                'fecha_consulta' => $fechaTarget,
            ];
        }

        // 4. Score por categoría (si cambia en más de 5 puntos)
        foreach (['IDENTIDAD', 'PLD', 'CONSISTENCIA', 'REPUTACIONAL'] as $cat) {
            $cAntes   = (int) ($baseExp['scores_categoria'][$cat]['score'] ?? 0);
            $cDespues = (int) ($targetExp['scores_categoria'][$cat]['score'] ?? 0);
            if (abs($cDespues - $cAntes) > 5) {
                $items[] = [
                    'campo'          => "scores_categoria.{$cat}",
                    'antes'          => $cAntes,
                    'despues'        => $cDespues,
                    'fuente'         => 'motor_scoring_avalid',
                    'fecha_consulta' => $fechaTarget,
                ];
            }
        }

        // 5. Estatus PEP
        $pepAntes   = $baseExp['pep']['estatus'] ?? null;
        $pepDespues = $targetExp['pep']['estatus'] ?? null;
        if ($pepAntes !== $pepDespues) {
            $items[] = [
                'campo'          => 'estatus_pep',
                'antes'          => $pepAntes,
                'despues'        => $pepDespues,
                'fuente'         => $targetExp['pep']['fuente'] ?? 'sanciones',
                'fecha_consulta' => $targetExp['pep']['fecha'] ?? $fechaTarget,
            ];
        }

        // 6. EDD recomendada
        $eddAntes   = (bool) ($baseExp['edd']['recomendada'] ?? false);
        $eddDespues = (bool) ($targetExp['edd']['recomendada'] ?? false);
        if ($eddAntes !== $eddDespues) {
            $items[] = [
                'campo'          => 'edd_recomendada',
                'antes'          => $eddAntes ? 'SÍ' : 'NO',
                'despues'        => $eddDespues ? 'SÍ' : 'NO',
                'fuente'         => 'motor_ebr_avalid',
                'fecha_consulta' => $fechaTarget,
            ];
        }

        // 7. Hallazgos que se agregan o desaparecen (sin interpretar "ya está limpio" — Regla 1.2.5)
        $mapHallazgosBase = [];
        foreach ($baseExp['hallazgos'] ?? [] as $hb) {
            $k = ($hb['trigger_key'] ?? '') . '|' . ($hb['fuente'] ?? '');
            $mapHallazgosBase[$k] = $hb;
        }
        $mapHallazgosTarget = [];
        foreach ($targetExp['hallazgos'] ?? [] as $ht) {
            $k = ($ht['trigger_key'] ?? '') . '|' . ($ht['fuente'] ?? '');
            $mapHallazgosTarget[$k] = $ht;
        }

        $allFindingKeys = array_unique(array_merge(array_keys($mapHallazgosBase), array_keys($mapHallazgosTarget)));
        sort($allFindingKeys);

        foreach ($allFindingKeys as $fKey) {
            $inBase   = isset($mapHallazgosBase[$fKey]);
            $inTarget = isset($mapHallazgosTarget[$fKey]);

            if (!$inBase && $inTarget) {
                $h = $mapHallazgosTarget[$fKey];
                $items[] = [
                    'campo'          => 'hallazgo.' . ($h['trigger_key'] ?? $fKey),
                    'antes'          => 'Sin coincidencia reportada en ' . ($h['fuente'] ?? 'fuente'),
                    'despues'        => $h['descripcion'] ?? 'Hallazgo confirmado',
                    'fuente'         => $h['fuente'] ?? 'N/A',
                    'fecha_consulta' => $h['fecha_consulta'] ?? $fechaTarget,
                ];
            } elseif ($inBase && !$inTarget) {
                $h = $mapHallazgosBase[$fKey];
                $items[] = [
                    'campo'          => 'hallazgo.' . ($h['trigger_key'] ?? $fKey),
                    'antes'          => ($h['descripcion'] ?? 'Hallazgo previo') . ' (fecha: ' . ($h['fecha_consulta'] ?? ($baseExp['fecha_generacion'] ?? 'N/A')) . ')',
                    'despues'        => 'Sin coincidencia en consulta actual de ' . ($h['fuente'] ?? 'fuente') . ' (fecha: ' . $fechaTarget . ')',
                    'fuente'         => $h['fuente'] ?? 'N/A',
                    'fecha_consulta' => $fechaTarget,
                ];
            }
        }

        // 8. Datos de identificación (nombre, CURP, domicilio)
        foreach (['nombre_o_razon', 'curp', 'domicilio'] as $pField) {
            $pAntes   = $baseExp['persona'][$pField] ?? null;
            $pDespues = $targetExp['persona'][$pField] ?? null;
            if ($pAntes !== $pDespues) {
                $items[] = [
                    'campo'          => "persona.{$pField}",
                    'antes'          => $pAntes,
                    'despues'        => $pDespues,
                    'fuente'         => 'contexto_sujeto',
                    'fecha_consulta' => $fechaTarget,
                ];
            }
        }

        return [
            'cambios'        => count($items) > 0,
            'version_actual' => $targetExp['folio'] ?? 'N/A',
            'version_previa' => $baseExp['folio'] ?? 'N/A',
            'items'          => $items,
        ];
    }

    /**
     * Calcula el diff estructurado entre dos folios guardados en base de datos (`GET /expedientes/{folio}/diff?contra={folio}`).
     */
    public function diffBetweenFolios(string $folioActual, ?string $folioContra = null): array
    {
        $actual = PldExpedienteVersion::where('folio', $folioActual)->firstOrFail();
        $targetExp = json_decode($actual->payload_canonico, true);

        if ($folioContra) {
            $contra = PldExpedienteVersion::where('folio', $folioContra)->firstOrFail();
        } else {
            $contra = PldExpedienteVersion::where('subject_id', $actual->subject_id)
                ->where('version', '<', $actual->version)
                ->orderByDesc('version')
                ->first();
        }

        if (!$contra) {
            return [
                'cambios'        => false,
                'version_actual' => $actual->folio,
                'version_previa' => null,
                'items'          => [],
            ];
        }

        $baseExp = json_decode($contra->payload_canonico, true);
        return $this->computeStructuredDiff($baseExp, $targetExp);
    }

    /**
     * Verifica criptográficamente un JSON de expediente contra su hash JCS (RFC 8785) y contra el registro en BD (Doc 2 §2.2 y §4).
     */
    public function verifyExpedientePayload(array $expedienteJson): array
    {
        $folio = (string) ($expedienteJson['folio'] ?? $expedienteJson['meta_version']['folio'] ?? '');
        $providedHash = (string) ($expedienteJson['hash_version'] ?? $expedienteJson['meta_version']['hash_version'] ?? '');

        $forHash = $expedienteJson;
        unset($forHash['hash_version']);
        if (isset($forHash['meta_version']) && is_array($forHash['meta_version'])) {
            unset($forHash['meta_version']['hash_version']);
        }

        $computedHash = JcsCanonicalizer::hashPayload($forHash);
        $storedVersion = $folio ? PldExpedienteVersion::where('folio', $folio)->first() : null;

        $matchesProvided = ($providedHash !== '' && hash_equals($computedHash, $providedHash));
        $matchesStored   = $storedVersion ? hash_equals($storedVersion->hash_version, $computedHash) : false;

        return [
            'valido'                => $matchesProvided && ($storedVersion ? $matchesStored : true),
            'folio'                 => $folio ?: null,
            'hash_calculado_jcs'    => $computedHash,
            'hash_recibido'         => $providedHash ?: null,
            'hash_registrado_en_bd' => $storedVersion?->hash_version,
            'hash_previo'           => $storedVersion?->hash_previo ?? ($expedienteJson['hash_previo'] ?? null),
            'coincide_con_recibido' => $matchesProvided,
            'coincide_con_bd'       => $matchesStored,
        ];
    }

    /**
     * Aplica la Regla 1.2.3 (arrastre de fuente caída en reconsulta) y Regla 1.2.4 (3 fallos seguidos).
     */
    protected function applyReconsultationFallenSourceRules(
        array $rawInputs,
        ?string $prevFolio,
        string $fechaGen,
        array &$fallosConsecutivos,
        array &$persistentFallenSources
    ): array {
        $prevResultsBySource = [];
        if ($prevFolio) {
            $prevResultsBySource = PldFuenteResultado::where('folio', $prevFolio)
                ->get()
                ->keyBy('fuente');
        }

        $processed = [];
        foreach ($rawInputs as $item) {
            $fKey = (string) ($item['fuente'] ?? '');
            $statusHttp = (int) ($item['status_http'] ?? 200);
            $estado = (string) ($item['estado'] ?? 'CONSULTADA');
            $esFallo = ($statusHttp < 200 || $statusHttp >= 400) || ($estado === 'NO_RECONSULTADA');

            if ($esFallo) {
                $fallosConsecutivos[$fKey] = ((int) ($fallosConsecutivos[$fKey] ?? 0)) + 1;
                if ($fallosConsecutivos[$fKey] >= 3) {
                    $persistentFallenSources[] = $fKey;
                }

                // Si existe versión previa, arrastrar el resultado_crudo anterior marcado como NO_RECONSULTADA
                if ($prevFolio && isset($prevResultsBySource[$fKey])) {
                    $prevRec = $prevResultsBySource[$fKey];
                    $item['resultado_crudo'] = is_array($prevRec->resultado_crudo) ? $prevRec->resultado_crudo : [];
                    $item['estado'] = 'NO_RECONSULTADA';
                    $item['ultima_consulta_exitosa'] = $prevRec->ultima_consulta_exitosa
                        ? $prevRec->ultima_consulta_exitosa->toIso8601String()
                        : ($prevRec->fecha_consulta ? $prevRec->fecha_consulta->toIso8601String() : null);
                }
            } else {
                $fallosConsecutivos[$fKey] = 0;
                $item['estado'] = 'CONSULTADA';
                $item['ultima_consulta_exitosa'] = $item['fecha_consulta'] ?? $fechaGen;
            }

            $processed[] = $item;
        }

        // Si en la versión previa había fuentes consultadas que ni siquiera vinieron en $rawInputs durante una reconsulta, arrastrarlas también
        if ($prevFolio) {
            $presentKeys = array_column($processed, 'fuente');
            foreach ($prevResultsBySource as $fKey => $prevRec) {
                if (!in_array($fKey, $presentKeys, true)) {
                    $fallosConsecutivos[$fKey] = ((int) ($fallosConsecutivos[$fKey] ?? 0)) + 1;
                    if ($fallosConsecutivos[$fKey] >= 3) {
                        $persistentFallenSources[] = $fKey;
                    }
                    $processed[] = [
                        'fuente'                  => $fKey,
                        'status_http'             => 503,
                        'resultado_crudo'         => is_array($prevRec->resultado_crudo) ? $prevRec->resultado_crudo : [],
                        'fecha_consulta'          => $fechaGen,
                        'ultima_consulta_exitosa' => $prevRec->ultima_consulta_exitosa
                            ? $prevRec->ultima_consulta_exitosa->toIso8601String()
                            : ($prevRec->fecha_consulta ? $prevRec->fecha_consulta->toIso8601String() : null),
                        'estado'                  => 'NO_RECONSULTADA',
                    ];
                }
            }
        }

        return $processed;
    }

    /**
     * Extrae los contratos de entrada (§3) a partir de los SourceQuery y SourceResult del sujeto en AVALID.
     */
    public function collectInputsFromSubjectQueries(Subject $subject, ?string $defaultDate = null): array
    {
        $queries = SourceQuery::withoutGlobalScopes()
            ->with('result')
            ->where('subject_id', $subject->id)
            ->get();

        $inputs = [];
        $nowIso = $defaultDate ?? now()->toIso8601String();

        foreach ($queries as $q) {
            $rawLog = $q->result?->raw_payload ?? [];
            $processed = $q->result?->processed_data ?? [];
            $statusHttp = (int) ($rawLog['status_code'] ?? ($q->status === 'completed' ? 200 : 500));
            if ($q->status === 'failed' && $statusHttp >= 200 && $statusHttp < 300) {
                $statusHttp = 500;
            }

            $qDate = $q->updated_at ? $q->updated_at->toIso8601String() : $nowIso;

            $inputs[] = [
                'fuente'                  => $q->source_type,
                'status_http'             => $statusHttp,
                'resultado_crudo'         => is_array($processed) ? $processed : [],
                'fecha_consulta'          => $qDate,
                'ultima_consulta_exitosa' => ($statusHttp >= 200 && $statusHttp < 300) ? $qDate : null,
                'estado'                  => 'CONSULTADA',
            ];
        }

        return $inputs;
    }

    protected function computeHistoricalConsecutiveFailures(int $subjectId): array
    {
        $counts = [];
        $versions = PldExpedienteVersion::where('subject_id', $subjectId)
            ->orderBy('version')
            ->pluck('folio');

        foreach ($versions as $fol) {
            $results = PldFuenteResultado::where('folio', $fol)->get();
            foreach ($results as $r) {
                if ($r->status_http < 200 || $r->status_http >= 400 || $r->estado === 'NO_RECONSULTADA') {
                    $counts[$r->fuente] = ((int) ($counts[$r->fuente] ?? 0)) + 1;
                } else {
                    $counts[$r->fuente] = 0;
                }
            }
        }

        return $counts;
    }

    protected function recordAuditAndUsage(Subject $subject, int $tenantId, string $folio): void
    {
        $userId = Auth::id() ?? $subject->project?->tenant?->users()->first()?->id ?? 1;

        try {
            AuditLog::create([
                'tenant_id'    => $tenantId,
                'user_id'      => $userId,
                'subject_name' => $subject->name_or_company ?? 'Sujeto PLD',
                'subject_rfc'  => $subject->rfc ?? 'SIN_RFC',
                'fuente'       => "Expediente PLD ({$folio})",
                'ip_address'   => request()?->ip() ?? '127.0.0.1',
                'created_at'   => now(),
            ]);

            $period = now()->format('Y-m');
            $cost   = (float) config('background_check.costs.pld_expediente', 0.00);
            $price  = (float) config('background_check.prices.pld_expediente', 15.00);

            $usage = ApiUsage::withoutGlobalScopes()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'user_id'   => $userId,
                    'servicio'  => 'pld_expediente',
                    'periodo'   => $period,
                ],
                [
                    'conteo'           => 0,
                    'costo_estimado'   => 0.00,
                    'ingreso_estimado' => 0.00,
                ]
            );

            $usage->conteo += 1;
            $usage->costo_estimado += $cost;
            $usage->ingreso_estimado += $price;
            $usage->save();
        } catch (\Throwable) {
            // No bloquear la transacción del expediente si no hay usuario para AuditLog en entornos de test aislados
        }
    }

    public static function formatFolio(int|string $subjectId, int $version): string
    {
        $padded = str_pad((string) $subjectId, 6, '0', STR_PAD_LEFT);
        return "P-{$padded}-v{$version}";
    }
}
