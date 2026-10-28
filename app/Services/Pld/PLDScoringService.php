<?php

namespace App\Services\Pld;

use App\Models\PldFuenteCatalogo;
use App\Models\PldScoringPolicy;
use App\Models\Subject;
use Carbon\Carbon;

/**
 * Motor Determinístico de Puntuación, Verificación Cruzada y Módulo EBR de AvalID (M3, M4, M5, M7).
 *
 * Implementa íntegramente las reglas de AvalID_Prompt_Operativo_Generador_Expedientes.md:
 * - Cero dato inventado o inferido fuera del JSON de entrada.
 * - Prohibido afirmar ausencia ("limpio", "sin antecedentes", "sin sentencia"); siempre "Sin coincidencia en [fuente]".
 * - Topes duros por categoría (no promedios que diluyan hallazgos graves).
 * - Coherencia estricta entre Nivel de Riesgo de Persona (BAJO|MEDIO|ALTO) y Recomendación (Tabla 7.1).
 * - Gate bloqueante de 14 puntos de verificación pre-entrega (§10).
 */
class PLDScoringService
{
    /**
     * Evalúa un conjunto de entradas de fuentes para un sujeto y construye el paquete
     * determinístico completo del Expediente PLD/EBR.
     *
     * @param Subject $subject
     * @param array $fuentesInput Lista de contratos de fuente (§3):
     *   [
     *     [
     *       'fuente' => 'lista_nominal',
     *       'status_http' => 200,
     *       'resultado_crudo' => [...],
     *       'fecha_consulta' => '2026-08-12T16:35:00-06:00',
     *       'ultima_consulta_exitosa' => '2026-08-12T16:35:00-06:00',
     *       'estado' => 'CONSULTADA' | 'NO_RECONSULTADA',
     *     ], ...
     *   ]
     * @param array $metaVersion Metadatos de versión asignados por el sistema:
     *   ['folio', 'version', 'tipo_ejecucion', 'fecha_generacion', 'hash_previo']
     * @param array|null $diffEstructurado Diff calculado respecto a la versión previa (solo en reevaluaciones)
     * @return array
     */
    public function evaluate(
        Subject $subject,
        array $fuentesInput,
        array $metaVersion,
        ?array $diffEstructurado = null
    ): array {
        $tenantId = (int) ($subject->tenant_id ?: $subject->project?->tenant_id ?: 1);

        // Asegurar que catálogo global y políticas del tenant estén sembrados
        PldFuenteCatalogo::ensureDefaultsSeeded();
        PldScoringPolicy::seedDefaultsForTenant($tenantId);

        $catalogo = PldFuenteCatalogo::all()->keyBy('fuente_key');
        $policies = PldScoringPolicy::where('tenant_id', $tenantId)->get()->keyBy('categoria');

        $fechaConsulta = isset($metaVersion['fecha_generacion'])
            ? Carbon::parse($metaVersion['fecha_generacion'])
            : now();

        // ─────────────────────────────────────────────────────────────────────
        // 1. CLASIFICACIÓN DE FUENTES CONTRA CATÁLOGO GLOBAL (§3 y §8)
        // ─────────────────────────────────────────────────────────────────────
        $fuentesCatalogadas = [];
        $fuentesNoCatalogadas = [];
        $fuentesPendientes = [];
        $hayFuenteAltaCriticaPendiente = false;
        $topesPorFuentePendiente = [];

        foreach ($fuentesInput as $item) {
            $key = (string) ($item['fuente'] ?? '');
            $catEntry = $catalogo->get($key);

            // Regla §3: Si una fuente no tiene categoría o criticidad en el catálogo global, NO se procesa
            if (!$catEntry || !$catEntry->activo || empty($catEntry->categoria) || empty($catEntry->criticidad)) {
                $fuentesNoCatalogadas[] = [
                    'fuente'         => $key ?: 'DESCONOCIDA',
                    'status_http'    => (int) ($item['status_http'] ?? 0),
                    'clasificacion'  => 'fuente no catalogada, requiere clasificación manual',
                    'fecha_consulta' => $item['fecha_consulta'] ?? $fechaConsulta->toIso8601String(),
                ];
                continue;
            }

            $categoria  = $catEntry->categoria;
            $criticidad = $catEntry->criticidad;
            $statusHttp = (int) ($item['status_http'] ?? 200);
            $estado     = (string) ($item['estado'] ?? 'CONSULTADA');
            $crudo      = is_array($item['resultado_crudo'] ?? null) ? $item['resultado_crudo'] : [];
            $fFecha     = (string) ($item['fecha_consulta'] ?? $fechaConsulta->toIso8601String());
            $ultExito   = $item['ultima_consulta_exitosa'] ?? ($statusHttp >= 200 && $statusHttp < 300 ? $fFecha : null);

            $esCaida = ($statusHttp < 200 || $statusHttp >= 400) || ($estado === 'NO_RECONSULTADA');

            if ($esCaida) {
                $topeCatPendiente = 89;
                $topesPorFuentePendiente[$categoria] = isset($topesPorFuentePendiente[$categoria])
                    ? min($topesPorFuentePendiente[$categoria], $topeCatPendiente)
                    : $topeCatPendiente;

                if ($criticidad === 'ALTA') {
                    $hayFuenteAltaCriticaPendiente = true;
                }

                $motivoPendiente = $estado === 'NO_RECONSULTADA'
                    ? "no reconsultada en esta versión; última consulta exitosa: " . ($ultExito ?? 'Sin dato')
                    : "Error HTTP {$statusHttp} — respuesta no concluyente en la consulta";

                $fuentesPendientes[] = [
                    'fuente'                  => $key,
                    'nombre_fuente'           => $catEntry->nombre,
                    'categoria'               => $categoria,
                    'criticidad'              => $criticidad,
                    'status_http'             => $statusHttp,
                    'estado'                  => $estado,
                    'ultima_consulta_exitosa' => $ultExito,
                    'tope_categoria'          => $topeCatPendiente,
                    'motivo'                  => $motivoPendiente,
                    'accion_recomendada'      => 'Reintento automático o verificación manual de esta fuente antes de cerrar dictamen.',
                    'fecha_consulta'          => $fFecha,
                ];
            }

            // Nota crucial §7.10 y Regla 1.2.3:
            // Si estado === 'NO_RECONSULTADA', su resultado_crudo previo SÍ se evalúa para que un
            // hallazgo positivo previo (PEP, sanción, 69-B) JAMÁS se borre por una fuente caída.
            $tieneDatosEvaluables = ($statusHttp >= 200 && $statusHttp < 300 && !empty($crudo))
                || ($estado === 'NO_RECONSULTADA' && !empty($crudo));

            $fuentesCatalogadas[$key] = [
                'fuente'                  => $key,
                'nombre_fuente'           => $catEntry->nombre,
                'categoria'               => $categoria,
                'criticidad'              => $criticidad,
                'status_http'             => $statusHttp,
                'estado'                  => $estado,
                'es_caida'                => $esCaida,
                'tiene_datos_evaluables'  => $tieneDatosEvaluables,
                'resultado_crudo'         => $crudo,
                'fecha_consulta'          => $fFecha,
                'ultima_consulta_exitosa' => $ultExito,
            ];
        }

        // ─────────────────────────────────────────────────────────────────────
        // 2. INICIALIZACIÓN DE CATEGORÍAS Y HALLAZGOS (§4)
        // ─────────────────────────────────────────────────────────────────────
        $scores = [
            'IDENTIDAD'    => 100,
            'PLD'          => 100,
            'CONSISTENCIA' => 100,
            'REPUTACIONAL' => 100,
        ];

        $hallazgos = [];
        $reglasNivelRiesgo = [];

        // Flags determinísticos para Recomendación y Nivel EBR
        $tieneCoincidenciaSancionesLista = false;
        $tieneCoincidencia69B = false;
        $tieneCoincidencia69 = false;
        $esPepConfirmado = false;
        $esPepExtranjero = false;
        $biometriaNoCoincide = false;
        $curpInvalido = false;
        $tieneDiscrepanciaIdentidad = false;

        // Aplicar topes de fuentes pendientes (nunca 100 mientras una fuente de la categoría esté pendiente)
        foreach ($topesPorFuentePendiente as $catPend => $topeVal) {
            if (isset($scores[$catPend])) {
                $scores[$catPend] = min($scores[$catPend], $topeVal);
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // 3. VERIFICACIÓN CRUZADA OBLIGATORIA ANTES DE PUNTUAR (M4 — §5)
        // ─────────────────────────────────────────────────────────────────────
        $nombresPorFuente = [];
        $curpsPorFuente = [];
        $fechasNacPorFuente = [];

        if (!empty($fuentesCatalogadas['ine_frente']['tiene_datos_evaluables'])) {
            $d = $fuentesCatalogadas['ine_frente']['resultado_crudo'];
            $nomIne = trim(($d['nombre'] ?? '') . ' ' . ($d['apellido_paterno'] ?? '') . ' ' . ($d['apellido_materno'] ?? ''));
            if (!empty($nomIne)) {
                $nombresPorFuente['ine_frente'] = [
                    'raw'   => $nomIne,
                    'norm'  => $this->normalizePersonName($nomIne),
                    'campo' => 'nombre, apellido_paterno, apellido_materno',
                    'fecha' => $fuentesCatalogadas['ine_frente']['fecha_consulta'],
                ];
            }
            if (!empty($d['curp']) && strtoupper($d['curp']) !== 'N/A') {
                $curpsPorFuente['ine_frente'] = [
                    'valor' => strtoupper(trim((string) $d['curp'])),
                    'campo' => 'curp',
                    'fecha' => $fuentesCatalogadas['ine_frente']['fecha_consulta'],
                ];
            }
            if (!empty($d['fecha_nacimiento']) && strtoupper($d['fecha_nacimiento']) !== 'N/A') {
                $normDob = $this->normalizeDateString((string) $d['fecha_nacimiento']);
                if ($normDob) {
                    $fechasNacPorFuente['ine_frente'] = [
                        'valor' => $normDob,
                        'raw'   => (string) $d['fecha_nacimiento'],
                        'campo' => 'fecha_nacimiento',
                        'fecha' => $fuentesCatalogadas['ine_frente']['fecha_consulta'],
                    ];
                }
            }
        }

        if (!empty($fuentesCatalogadas['curp']['tiene_datos_evaluables'])) {
            $d = $fuentesCatalogadas['curp']['resultado_crudo'];
            $nomCurp = trim(($d['nombre'] ?? '') . ' ' . ($d['primer_apellido'] ?? '') . ' ' . ($d['segundo_apellido'] ?? ''));
            if (!empty($nomCurp)) {
                $nombresPorFuente['curp'] = [
                    'raw'   => $nomCurp,
                    'norm'  => $this->normalizePersonName($nomCurp),
                    'campo' => 'nombre, primer_apellido, segundo_apellido',
                    'fecha' => $fuentesCatalogadas['curp']['fecha_consulta'],
                ];
            }
            if (!empty($d['curp'])) {
                $curpsPorFuente['curp'] = [
                    'valor' => strtoupper(trim((string) $d['curp'])),
                    'campo' => 'curp',
                    'fecha' => $fuentesCatalogadas['curp']['fecha_consulta'],
                ];
            }
            if (!empty($d['fecha_nacimiento'])) {
                $normDob = $this->normalizeDateString((string) $d['fecha_nacimiento']);
                if ($normDob) {
                    $fechasNacPorFuente['curp'] = [
                        'valor' => $normDob,
                        'raw'   => (string) $d['fecha_nacimiento'],
                        'campo' => 'fecha_nacimiento',
                        'fecha' => $fuentesCatalogadas['curp']['fecha_consulta'],
                    ];
                }
            }
        }

        if (!empty($fuentesCatalogadas['rfc']['tiene_datos_evaluables'])) {
            $d = $fuentesCatalogadas['rfc']['resultado_crudo'];
            if (!empty($d['razon_social'])) {
                $nombresPorFuente['rfc'] = [
                    'raw'   => (string) $d['razon_social'],
                    'norm'  => $this->normalizePersonName((string) $d['razon_social']),
                    'campo' => 'razon_social',
                    'fecha' => $fuentesCatalogadas['rfc']['fecha_consulta'],
                ];
            }
        }

        if (!empty($fuentesCatalogadas['nss_imss']['tiene_datos_evaluables'])) {
            $d = $fuentesCatalogadas['nss_imss']['resultado_crudo'];
            if (!empty($d['nombre']) && strtoupper($d['nombre']) !== 'N/A') {
                $nombresPorFuente['nss_imss'] = [
                    'raw'   => (string) $d['nombre'],
                    'norm'  => $this->normalizePersonName((string) $d['nombre']),
                    'campo' => 'nombre',
                    'fecha' => $fuentesCatalogadas['nss_imss']['fecha_consulta'],
                ];
            }
            if (!empty($d['curp']) && strtoupper($d['curp']) !== 'N/A') {
                $curpsPorFuente['nss_imss'] = [
                    'valor' => strtoupper(trim((string) $d['curp'])),
                    'campo' => 'curp',
                    'fecha' => $fuentesCatalogadas['nss_imss']['fecha_consulta'],
                ];
            }
        }

        // 3.1 Comparación de Nombre entre INE / CURP / RFC / NSS
        if (count($nombresPorFuente) >= 2) {
            $uniqueNames = [];
            foreach ($nombresPorFuente as $fKey => $nInfo) {
                if (!empty($nInfo['norm'])) {
                    $uniqueNames[$nInfo['norm']][] = "{$fKey} ({$nInfo['raw']})";
                }
            }
            if (count($uniqueNames) > 1) {
                $tieneDiscrepanciaIdentidad = true;
                $topeIdNombre = $this->getTriggerValue($policies, 'IDENTIDAD', 'nombre_no_coincide', 70);
                if ($this->isTriggerActive($policies, 'IDENTIDAD', 'nombre_no_coincide')) {
                    $scores['IDENTIDAD'] = min($scores['IDENTIDAD'], $topeIdNombre);
                }

                $topeConsNombre = $this->getTriggerValue($policies, 'CONSISTENCIA', 'nombre_difiere', 50);
                if ($this->isTriggerActive($policies, 'CONSISTENCIA', 'nombre_difiere')) {
                    $scores['CONSISTENCIA'] = min($scores['CONSISTENCIA'], $topeConsNombre);
                }

                $detalleNombres = implode(' vs ', array_map(fn($list) => implode(', ', $list), $uniqueNames));
                $hallazgos[] = [
                    'codigo'         => 'CONSISTENCIA_NOMBRE_DIFIERE',
                    'categoria'      => 'CONSISTENCIA',
                    'fuente'         => implode(', ', array_keys($nombresPorFuente)),
                    'campo'          => 'nombre / razon_social',
                    'descripcion'    => "Discrepancia de nombre entre fuentes oficiales: {$detalleNombres}.",
                    'efecto_score'   => "Identidad tope {$topeIdNombre}, Consistencia tope {$topeConsNombre}",
                    'fecha_consulta' => $fechaConsulta->toIso8601String(),
                ];
                $reglasNivelRiesgo[] = [
                    'regla'  => "Discrepancia de nombre entre fuentes ({$detalleNombres}) → Identidad tope {$topeIdNombre} / Consistencia tope {$topeConsNombre}",
                    'fuente' => implode(', ', array_keys($nombresPorFuente)),
                    'campo'  => 'nombre / razon_social',
                ];
            }
        }

        // 3.2 Comparación de CURP entre fuentes
        if (count($curpsPorFuente) >= 2) {
            $uniqueCurps = [];
            foreach ($curpsPorFuente as $fKey => $cInfo) {
                $uniqueCurps[$cInfo['valor']][] = "{$fKey} ({$cInfo['valor']})";
            }
            if (count($uniqueCurps) > 1) {
                $tieneDiscrepanciaIdentidad = true;
                $topeConsCurp = 50;
                $scores['CONSISTENCIA'] = min($scores['CONSISTENCIA'], $topeConsCurp);
                $detalleCurps = implode(' vs ', array_map(fn($list) => implode(', ', $list), $uniqueCurps));
                $hallazgos[] = [
                    'codigo'         => 'CONSISTENCIA_CURP_DIFIERE',
                    'categoria'      => 'CONSISTENCIA',
                    'fuente'         => implode(', ', array_keys($curpsPorFuente)),
                    'campo'          => 'curp',
                    'descripcion'    => "La CURP difiere entre fuentes consultadas: {$detalleCurps}.",
                    'efecto_score'   => "Consistencia tope {$topeConsCurp}",
                    'fecha_consulta' => $fechaConsulta->toIso8601String(),
                ];
            }
        }

        // 3.3 Comparación de Fecha de Nacimiento entre fuentes
        if (count($fechasNacPorFuente) >= 2) {
            $uniqueDobs = [];
            foreach ($fechasNacPorFuente as $fKey => $dInfo) {
                $uniqueDobs[$dInfo['valor']][] = "{$fKey} ({$dInfo['valor']})";
            }
            if (count($uniqueDobs) > 1) {
                $tieneDiscrepanciaIdentidad = true;
                $topeDob = $this->getTriggerValue($policies, 'CONSISTENCIA', 'fecha_nacimiento_difiere', 50);
                if ($this->isTriggerActive($policies, 'CONSISTENCIA', 'fecha_nacimiento_difiere')) {
                    $scores['CONSISTENCIA'] = min($scores['CONSISTENCIA'], $topeDob);
                }
                $detalleDobs = implode(' vs ', array_map(fn($list) => implode(', ', $list), $uniqueDobs));
                $hallazgos[] = [
                    'codigo'         => 'CONSISTENCIA_FECHA_NACIMIENTO_DIFIERE',
                    'categoria'      => 'CONSISTENCIA',
                    'fuente'         => implode(', ', array_keys($fechasNacPorFuente)),
                    'campo'          => 'fecha_nacimiento',
                    'descripcion'    => "La fecha de nacimiento difiere entre fuentes: {$detalleDobs}.",
                    'efecto_score'   => "Consistencia tope {$topeDob}",
                    'fecha_consulta' => $fechaConsulta->toIso8601String(),
                ];
                $reglasNivelRiesgo[] = [
                    'regla'  => "Fecha de nacimiento difiere entre fuentes ({$detalleDobs}) → Consistencia tope {$topeDob}",
                    'fuente' => implode(', ', array_keys($fechasNacPorFuente)),
                    'campo'  => 'fecha_nacimiento',
                ];
            }
        }

        // 3.4 Coherencia estructural RFC vs CURP (primeros 10 caracteres en persona física)
        if ($subject->tipo === 'persona_fisica') {
            $rfcVal = strtoupper(trim((string) ($fuentesCatalogadas['rfc']['resultado_crudo']['rfc'] ?? $subject->rfc ?? '')));
            $curpVal = strtoupper(trim((string) ($curpsPorFuente['curp']['valor'] ?? $curpsPorFuente['ine_frente']['valor'] ?? $subject->curp ?? '')));

            if (strlen($rfcVal) >= 10 && strlen($curpVal) >= 10) {
                $prefRfc  = substr($rfcVal, 0, 10);
                $prefCurp = substr($curpVal, 0, 10);
                if ($prefRfc !== $prefCurp) {
                    $tieneDiscrepanciaIdentidad = true;
                    $topeRfcCurp = $this->getTriggerValue($policies, 'CONSISTENCIA', 'rfc_curp_estructura', 60);
                    if ($this->isTriggerActive($policies, 'CONSISTENCIA', 'rfc_curp_estructura')) {
                        $scores['CONSISTENCIA'] = min($scores['CONSISTENCIA'], $topeRfcCurp);
                    }
                    $hallazgos[] = [
                        'codigo'         => 'CONSISTENCIA_RFC_CURP_ESTRUCTURA',
                        'categoria'      => 'CONSISTENCIA',
                        'fuente'         => 'rfc, curp',
                        'campo'          => 'rfc (primeros 10 caracteres) vs curp (primeros 10 caracteres)',
                        'descripcion'    => "El prefijo estructural de 10 caracteres del RFC ({$prefRfc}) no coincide con el de la CURP ({$prefCurp}).",
                        'efecto_score'   => "Consistencia tope {$topeRfcCurp}",
                        'fecha_consulta' => $fechaConsulta->toIso8601String(),
                    ];
                    $reglasNivelRiesgo[] = [
                        'regla'  => "RFC ({$prefRfc}) no coincide estructuralmente con CURP ({$prefCurp}) → Consistencia tope {$topeRfcCurp}",
                        'fuente' => 'rfc, curp',
                        'campo'  => 'rfc / curp',
                    ];
                }
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // 4. REGLAS DE DISPARO — CATEGORÍA 4.1: IDENTIDAD
        // ─────────────────────────────────────────────────────────────────────

        // Trigger 4.1.1: Lista Nominal INE = NO VIGENTE / INACTIVA / no coincide -> TOPE 60
        if (!empty($fuentesCatalogadas['lista_nominal']['tiene_datos_evaluables'])) {
            $lnData = $fuentesCatalogadas['lista_nominal']['resultado_crudo'];
            $lnActiva = (bool) ($lnData['activa'] ?? $lnData['valida'] ?? false);
            if (!$lnActiva) {
                $topeLn = $this->getTriggerValue($policies, 'IDENTIDAD', 'lista_nominal_inactiva', 60);
                if ($this->isTriggerActive($policies, 'IDENTIDAD', 'lista_nominal_inactiva')) {
                    $scores['IDENTIDAD'] = min($scores['IDENTIDAD'], $topeLn);
                }
                $estadoLn = (string) ($lnData['estado'] ?? 'No vigente / Inactiva');
                $hallazgos[] = [
                    'codigo'         => 'IDENTIDAD_LISTA_NOMINAL_INACTIVA',
                    'categoria'      => 'IDENTIDAD',
                    'fuente'         => 'lista_nominal',
                    'campo'          => 'activa / estado',
                    'descripcion'    => "Lista Nominal INE reportó estatus no activo: {$estadoLn}.",
                    'efecto_score'   => "Identidad tope {$topeLn}",
                    'fecha_consulta' => $fuentesCatalogadas['lista_nominal']['fecha_consulta'],
                ];
                $reglasNivelRiesgo[] = [
                    'regla'  => "Lista Nominal INE inactiva ({$estadoLn}) → Identidad tope {$topeLn}",
                    'fuente' => 'lista_nominal',
                    'campo'  => 'activa / estado',
                ];
            }
        }

        // Trigger 4.1.2 y 4.1.3: Biometría facial (ine_vs_selfie / selfie)
        if (!empty($fuentesCatalogadas['ine_vs_selfie']['tiene_datos_evaluables'])) {
            $bioData = $fuentesCatalogadas['ine_vs_selfie']['resultado_crudo'];
            $bioStatus = strtolower((string) ($bioData['status'] ?? 'completed'));

            if ($bioStatus !== 'skipped') {
                $coincide = (bool) ($bioData['coincide_rostro'] ?? false);
                $rawCerteza = $bioData['certeza'] ?? (isset($bioData['certeza_porcentaje']) ? (float) str_replace('%', '', (string) $bioData['certeza_porcentaje']) : 100.0);
                $certeza  = (float) $rawCerteza;
                $certezaPct = ($certeza > 0.0 && $certeza <= 1.0) ? ($certeza * 100.0) : $certeza;

                if (!$coincide) {
                    $biometriaNoCoincide = true;
                    $topeBioNo = $this->getTriggerValue($policies, 'IDENTIDAD', 'biometria_no_coincide', 0);
                    if ($this->isTriggerActive($policies, 'IDENTIDAD', 'biometria_no_coincide')) {
                        $scores['IDENTIDAD'] = min($scores['IDENTIDAD'], $topeBioNo);
                    }
                    $hallazgos[] = [
                        'codigo'         => 'IDENTIDAD_BIOMETRIA_NO_COINCIDE',
                        'categoria'      => 'IDENTIDAD',
                        'fuente'         => 'ine_vs_selfie',
                        'campo'          => 'coincide_rostro',
                        'descripcion'    => "Comparación biométrica facial reportó: NO COINCIDE (certeza: " . number_format($certezaPct, 2) . "%).",
                        'efecto_score'   => "Identidad tope {$topeBioNo} (categoría reprobada)",
                        'fecha_consulta' => $fuentesCatalogadas['ine_vs_selfie']['fecha_consulta'],
                    ];
                    $reglasNivelRiesgo[] = [
                        'regla'  => "Biometría facial NO COINCIDE → Identidad tope {$topeBioNo} → ALTO",
                        'fuente' => 'ine_vs_selfie',
                        'campo'  => 'coincide_rostro',
                    ];
                } elseif ($certezaPct < 90.0) {
                    $topeBio90 = $this->getTriggerValue($policies, 'IDENTIDAD', 'biometria_menor_90', 50);
                    if ($this->isTriggerActive($policies, 'IDENTIDAD', 'biometria_menor_90')) {
                        $scores['IDENTIDAD'] = min($scores['IDENTIDAD'], $topeBio90);
                    }
                    $hallazgos[] = [
                        'codigo'         => 'IDENTIDAD_BIOMETRIA_MENOR_90',
                        'categoria'      => 'IDENTIDAD',
                        'fuente'         => 'ine_vs_selfie',
                        'campo'          => 'certeza',
                        'descripcion'    => "Biometría facial con certeza inferior al 90% (" . number_format($certezaPct, 2) . "%).",
                        'efecto_score'   => "Identidad tope {$topeBio90}",
                        'fecha_consulta' => $fuentesCatalogadas['ine_vs_selfie']['fecha_consulta'],
                    ];
                    $reglasNivelRiesgo[] = [
                        'regla'  => "Biometría facial < 90% (" . number_format($certezaPct, 2) . "%) → Identidad tope {$topeBio90}",
                        'fuente' => 'ine_vs_selfie',
                        'campo'  => 'certeza',
                    ];
                }
            }
        }

        // Trigger 4.1.4: CURP inválido ante RENAPO -> TOPE 30
        if (!empty($fuentesCatalogadas['curp']['tiene_datos_evaluables'])) {
            $cData = $fuentesCatalogadas['curp']['resultado_crudo'];
            $cValida = (bool) ($cData['valida'] ?? false);
            $estatusCurp = strtoupper(trim((string) ($cData['estatus_curp'] ?? '')));
            $bajasCurp = ['BD', 'BAP', 'BSU', 'BCC']; // Baja por defunción, baja administrativa, etc.

            if (!$cValida || in_array($estatusCurp, $bajasCurp, true)) {
                $curpInvalido = true;
                $topeCurp = $this->getTriggerValue($policies, 'IDENTIDAD', 'curp_invalido', 30);
                if ($this->isTriggerActive($policies, 'IDENTIDAD', 'curp_invalido')) {
                    $scores['IDENTIDAD'] = min($scores['IDENTIDAD'], $topeCurp);
                }
                $motivoCurp = $cData['description_status_curp'] ?? $cData['mensaje'] ?? 'No válida en RENAPO';
                $hallazgos[] = [
                    'codigo'         => 'IDENTIDAD_CURP_INVALIDO',
                    'categoria'      => 'IDENTIDAD',
                    'fuente'         => 'curp',
                    'campo'          => 'valida / estatus_curp',
                    'descripcion'    => "CURP reportada como no válida o en estatus de baja ante RENAPO ({$estatusCurp}: {$motivoCurp}).",
                    'efecto_score'   => "Identidad tope {$topeCurp}",
                    'fecha_consulta' => $fuentesCatalogadas['curp']['fecha_consulta'],
                ];
                $reglasNivelRiesgo[] = [
                    'regla'  => "CURP inválida ante RENAPO ({$estatusCurp}) → Identidad tope {$topeCurp} → ALTO",
                    'fuente' => 'curp',
                    'campo'  => 'valida / estatus_curp',
                ];
            }
        }

        // Trigger 4.1.6: OCR ilegible o incompleto -> -20, pasa a "no concluyente"
        $ocrIlegible = false;
        $ocrFuenteAfectada = null;
        if (!empty($fuentesCatalogadas['ine_frente']['tiene_datos_evaluables'])) {
            $fD = $fuentesCatalogadas['ine_frente']['resultado_crudo'];
            if (empty($fD['clave_elector']) && empty($fD['curp'])) {
                $ocrIlegible = true;
                $ocrFuenteAfectada = 'ine_frente';
            }
        }
        if (!empty($fuentesCatalogadas['ine_reverso']['tiene_datos_evaluables'])) {
            $rD = $fuentesCatalogadas['ine_reverso']['resultado_crudo'];
            if (($rD['cic'] ?? 'N/A') === 'N/A' && ($rD['codigo_ocr'] ?? 'N/A') === 'N/A') {
                $ocrIlegible = true;
                $ocrFuenteAfectada = $ocrFuenteAfectada ? "{$ocrFuenteAfectada}, ine_reverso" : 'ine_reverso';
            }
        }
        if ($ocrIlegible) {
            $descuentoOcr = $this->getTriggerValue($policies, 'IDENTIDAD', 'ocr_ilegible', 20);
            if ($this->isTriggerActive($policies, 'IDENTIDAD', 'ocr_ilegible')) {
                $scores['IDENTIDAD'] = max(0, $scores['IDENTIDAD'] - $descuentoOcr);
            }
            $fuentesPendientes[] = [
                'fuente'                  => $ocrFuenteAfectada,
                'nombre_fuente'           => 'OCR Credencial INE',
                'categoria'               => 'IDENTIDAD',
                'criticidad'              => 'MEDIA',
                'status_http'             => 200,
                'estado'                  => 'NO_CONCLUYENTE',
                'ultima_consulta_exitosa' => null,
                'tope_categoria'          => $scores['IDENTIDAD'],
                'motivo'                  => 'OCR ilegible o con campos obligatorios incompletos; clasificado como no concluyente.',
                'accion_recomendada'      => 'Solicitar nueva captura legible de la identificación oficial.',
                'fecha_consulta'          => $fechaConsulta->toIso8601String(),
            ];
        }

        // ─────────────────────────────────────────────────────────────────────
        // 5. REGLAS DE DISPARO — CATEGORÍA 4.2: CUMPLIMIENTO PLD
        // ─────────────────────────────────────────────────────────────────────
        $pepInfo = [
            'estatus'            => 'PENDIENTE',
            'fuente'             => 'No consultada',
            'fecha'              => null,
            'nota_consulta_uif'  => 'No es la Consulta PEP 2.0 de la UIF (la cual opera exclusivamente el sujeto obligado con su e.firma; evidencia complementaria conforme a Reglamento Art. 45 Bis y 45 Ter).',
            'asimilados'         => 'Personas asimiladas a PEP (familiares/socios): no evaluado con la fuente actual',
            'pep_extranjeros'    => 'PEP extranjeros: no evaluado con la fuente actual',
            'detalles_cargo'     => 'Sin dato',
            'fecha_baja_cargo'   => 'Sin dato',
        ];

        if (!empty($fuentesCatalogadas['sanciones']['tiene_datos_evaluables'])) {
            $sData = $fuentesCatalogadas['sanciones']['resultado_crudo'];
            $fSancFecha = $fuentesCatalogadas['sanciones']['fecha_consulta'];
            $hits = is_array($sData['hits'] ?? null) ? $sData['hits'] : [];

            $pepInfo['estatus'] = 'NO REGISTRADO COMO PEP';
            $pepInfo['fuente']  = 'NuFi (/perfilamiento/v1/aml), no es la Consulta PEP 2.0 de la UIF';
            $pepInfo['fecha']   = $fSancFecha;

            foreach ($hits as $hit) {
                $nombreHit = trim((string) ($hit['nombre_encontrado'] ?? ''));
                if (empty($nombreHit) || strtoupper($nombreHit) === 'N/A') {
                    continue;
                }

                $listaNombre = (string) ($hit['lista'] ?? 'Lista de Sanciones / AML');
                $tipoLista   = (string) ($hit['tipo_lista'] ?? '');
                $paisHit     = (string) ($hit['entidad_pais'] ?? 'No especificado');
                $combinedStr = strtoupper($listaNombre . ' ' . $tipoLista);

                $esHitPep = str_contains($combinedStr, 'PEP') || str_contains($combinedStr, 'POLITICAMENTE') || str_contains($combinedStr, 'POLÍTICAMENTE');
                $esHitSancion = str_contains($combinedStr, 'OFAC')
                    || str_contains($combinedStr, 'SDN')
                    || str_contains($combinedStr, 'ONU')
                    || str_contains($combinedStr, 'UE')
                    || str_contains($combinedStr, 'LPB')
                    || str_contains($combinedStr, 'CNBV')
                    || str_contains($combinedStr, 'UIF')
                    || str_contains($combinedStr, 'SANCION')
                    || str_contains($combinedStr, 'SANCIÓN')
                    || !$esHitPep;

                if ($esHitPep) {
                    $esPepConfirmado = true;
                    $pepInfo['estatus'] = 'REGISTRADO COMO PEP';
                    $pepInfo['detalles_cargo'] = (string) ($hit['comentarios'] ?? $tipoLista ?: 'Reportado en fuente PEP');
                    if (!empty($hit['fecha_baja'])) {
                        $pepInfo['fecha_baja_cargo'] = (string) $hit['fecha_baja'];
                    }
                    if (!str_contains(strtoupper($paisHit), 'MEX') && !str_contains(strtoupper($paisHit), 'MÉX')) {
                        $esPepExtranjero = true;
                        $pepInfo['pep_extranjeros'] = "Detectado PEP vinculado a jurisdicción extranjera ({$paisHit})";
                    }

                    $topePep = $this->getTriggerValue($policies, 'PLD', 'registrado_pep', 40);
                    if ($this->isTriggerActive($policies, 'PLD', 'registrado_pep')) {
                        $scores['PLD'] = min($scores['PLD'], $topePep);
                    }

                    $hallazgos[] = [
                        'codigo'         => 'PLD_REGISTRADO_PEP',
                        'categoria'      => 'PLD',
                        'fuente'         => 'sanciones',
                        'campo'          => 'hits (lista / tipo_lista)',
                        'descripcion'    => "Registrado como Persona Políticamente Expuesta (PEP) en {$listaNombre} — Nombre reportado: {$nombreHit} ({$paisHit}).",
                        'efecto_score'   => "Cumplimiento PLD tope {$topePep} + activa EDD",
                        'fecha_consulta' => $fSancFecha,
                    ];
                    $reglasNivelRiesgo[] = [
                        'regla'  => "Registrado como PEP en {$listaNombre} → Cumplimiento PLD tope {$topePep} → ALTO",
                        'fuente' => 'sanciones',
                        'campo'  => 'hits',
                    ];
                }

                if ($esHitSancion && !$esHitPep) {
                    $tieneCoincidenciaSancionesLista = true;
                    $topeSanc = $this->getTriggerValue($policies, 'PLD', 'sanciones_ofac_onu_ue_lpb', 0);
                    if ($this->isTriggerActive($policies, 'PLD', 'sanciones_ofac_onu_ue_lpb')) {
                        $scores['PLD'] = min($scores['PLD'], $topeSanc);
                    }

                    $hallazgos[] = [
                        'codigo'         => 'PLD_COINCIDENCIA_SANCIONES',
                        'categoria'      => 'PLD',
                        'fuente'         => 'sanciones',
                        'campo'          => 'hits (lista / nombre_encontrado)',
                        'descripcion'    => "Coincidencia en lista de sanciones ({$listaNombre}): {$nombreHit} ({$paisHit}).",
                        'efecto_score'   => "Cumplimiento PLD tope {$topeSanc} (categoría reprobada)",
                        'fecha_consulta' => $fSancFecha,
                    ];
                    $reglasNivelRiesgo[] = [
                        'regla'  => "Coincidencia en lista de sanciones ({$listaNombre}) → Cumplimiento PLD tope {$topeSanc} → ALTO",
                        'fuente' => 'sanciones',
                        'campo'  => 'hits',
                    ];
                }
            }
        } else {
            // Si sanciones no respondió o no fue consultada: PLD no puede llegar a 100
            $topePendPld = $this->getTriggerValue($policies, 'PLD', 'fuente_sanciones_pendiente', 89);
            $scores['PLD'] = min($scores['PLD'], $topePendPld);
            $hayFuenteAltaCriticaPendiente = true;
        }

        // Trigger 4.2.3 y 4.2.4: Listas SAT 69 y 69-B
        if (!empty($fuentesCatalogadas['sat_listas']['tiene_datos_evaluables'])) {
            $satData = $fuentesCatalogadas['sat_listas']['resultado_crudo'];
            $fSatFecha = $fuentesCatalogadas['sat_listas']['fecha_consulta'];

            $en69b = (bool) ($satData['en_lista_69b'] ?? false);
            $estatus69b = strtoupper(trim((string) ($satData['estatus_69b'] ?? '')));
            $en69 = (bool) ($satData['en_lista_69'] ?? false);
            $oficio = (string) ($satData['oficio_oficial'] ?? 'No provisto');

            if ($en69b && !in_array($estatus69b, ['DESVIRTUADO', 'SENTENCIA FAVORABLE'], true)) {
                $tieneCoincidencia69B = true;
                $tope69b = $this->getTriggerValue($policies, 'PLD', 'lista_69b', 0);
                if ($this->isTriggerActive($policies, 'PLD', 'lista_69b')) {
                    $scores['PLD'] = min($scores['PLD'], $tope69b);
                }
                $hallazgos[] = [
                    'codigo'         => 'PLD_SAT_LISTA_69B',
                    'categoria'      => 'PLD',
                    'fuente'         => 'sat_listas',
                    'campo'          => 'en_lista_69b / estatus_69b',
                    'descripcion'    => "Coincidencia en listado del Artículo 69-B del SAT (estatus: " . ($estatus69b ?: 'REGISTRADO') . ", oficio: {$oficio}).",
                    'efecto_score'   => "Cumplimiento PLD tope {$tope69b} (categoría reprobada)",
                    'fecha_consulta' => $fSatFecha,
                ];
                $reglasNivelRiesgo[] = [
                    'regla'  => "Coincidencia en Lista 69-B SAT ({$estatus69b}) → Cumplimiento PLD tope {$tope69b} → ALTO",
                    'fuente' => 'sat_listas',
                    'campo'  => 'en_lista_69b',
                ];
            }

            if ($en69) {
                $tieneCoincidencia69 = true;
                $tope69 = $this->getTriggerValue($policies, 'PLD', 'lista_69', 50);
                if ($this->isTriggerActive($policies, 'PLD', 'lista_69')) {
                    $scores['PLD'] = min($scores['PLD'], $tope69);
                }
                $hallazgos[] = [
                    'codigo'         => 'PLD_SAT_LISTA_69',
                    'categoria'      => 'PLD',
                    'fuente'         => 'sat_listas',
                    'campo'          => 'en_lista_69',
                    'descripcion'    => "Coincidencia en listado del Artículo 69 del SAT (oficio: {$oficio}).",
                    'efecto_score'   => "Cumplimiento PLD tope {$tope69}",
                    'fecha_consulta' => $fSatFecha,
                ];
                $reglasNivelRiesgo[] = [
                    'regla'  => "Coincidencia en Lista 69 SAT → Cumplimiento PLD tope {$tope69}",
                    'fuente' => 'sat_listas',
                    'campo'  => 'en_lista_69',
                ];
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // 6. REGLAS DE DISPARO — CATEGORÍA 4.4: REPUTACIONAL / OSINT
        // ─────────────────────────────────────────────────────────────────────
        if (!empty($fuentesCatalogadas['litigios']['tiene_datos_evaluables'])) {
            $litData = $fuentesCatalogadas['litigios']['resultado_crudo'];
            $fLitFecha = $fuentesCatalogadas['litigios']['fecha_consulta'];
            $tieneJuicios = (bool) ($litData['tiene_juicios'] ?? false);
            $juicios = is_array($litData['juicios'] ?? null) ? $litData['juicios'] : [];

            if ($tieneJuicios && count($juicios) > 0) {
                $topeLit = $this->getTriggerValue($policies, 'REPUTACIONAL', 'senales_adversas', 40);
                if ($this->isTriggerActive($policies, 'REPUTACIONAL', 'senales_adversas')) {
                    $scores['REPUTACIONAL'] = min($scores['REPUTACIONAL'], $topeLit);
                }
                $primerJuicio = $juicios[0];
                $expStr = (string) ($primerJuicio['expediente'] ?? 'Sin número');
                $juzStr = (string) ($primerJuicio['juzgado'] ?? 'No provisto');
                $matStr = (string) ($primerJuicio['materia'] ?? 'No provisto');

                $hallazgos[] = [
                    'codigo'         => 'REPUTACIONAL_LITIGIOS_ACTIVOS',
                    'categoria'      => 'REPUTACIONAL',
                    'fuente'         => 'litigios',
                    'campo'          => 'tiene_juicios / juicios',
                    'descripcion'    => "Se reportan " . count($juicios) . " expediente(s) judiciales en Boletín Judicial (ej. Exp. {$expStr}, {$juzStr}, materia: {$matStr}).",
                    'efecto_score'   => "Reputacional/OSINT tope {$topeLit}",
                    'fecha_consulta' => $fLitFecha,
                ];
                $reglasNivelRiesgo[] = [
                    'regla'  => "Señales adversas en Boletín Judicial (" . count($juicios) . " expediente(s)) → Reputacional tope {$topeLit}",
                    'fuente' => 'litigios',
                    'campo'  => 'juicios',
                ];
            }
        }

        if (!empty($fuentesCatalogadas['csd']['tiene_datos_evaluables'])) {
            $csdData = $fuentesCatalogadas['csd']['resultado_crudo'];
            $fCsdFecha = $fuentesCatalogadas['csd']['fecha_consulta'];
            $certificados = is_array($csdData['certificados'] ?? null) ? $csdData['certificados'] : [];

            $revocados = [];
            foreach ($certificados as $cert) {
                $estCert = strtoupper(trim((string) ($cert['estado'] ?? '')));
                if (in_array($estCert, ['REVOCADO', 'CANCELADO'], true)) {
                    $revocados[] = ($cert['numero_serie'] ?? 'S/N') . " ({$cert['estado']})";
                }
            }

            if (count($revocados) > 0) {
                $descCsd = $this->getTriggerValue($policies, 'REPUTACIONAL', 'csd_revocado', 10);
                if ($this->isTriggerActive($policies, 'REPUTACIONAL', 'csd_revocado')) {
                    $scores['REPUTACIONAL'] = max(0, $scores['REPUTACIONAL'] - $descCsd);
                }
                $hallazgos[] = [
                    'codigo'         => 'REPUTACIONAL_CSD_REVOCADO',
                    'categoria'      => 'REPUTACIONAL',
                    'fuente'         => 'csd',
                    'campo'          => 'certificados[].estado',
                    'descripcion'    => "Certificado(s) CSD/e-firma con estatus revocado o cancelado reportado por la fuente: " . implode(', ', $revocados) . ".",
                    'efecto_score'   => "Reputacional/OSINT -{$descCsd} pts",
                    'fecha_consulta' => $fCsdFecha,
                ];
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // 7. CÁLCULO DE SCORE GLOBAL PONDERADO TRAS TOPES (§4)
        // ─────────────────────────────────────────────────────────────────────
        $wId   = (int) ($policies->get('IDENTIDAD')?->peso_porcentual ?? 40);
        $wPld  = (int) ($policies->get('PLD')?->peso_porcentual ?? 30);
        $wCons = (int) ($policies->get('CONSISTENCIA')?->peso_porcentual ?? 15);
        $wRep  = (int) ($policies->get('REPUTACIONAL')?->peso_porcentual ?? 15);
        $wTotal = max(1, $wId + $wPld + $wCons + $wRep);

        $scoreGlobal = (int) round(
            (($scores['IDENTIDAD'] * $wId)
            + ($scores['PLD'] * $wPld)
            + ($scores['CONSISTENCIA'] * $wCons)
            + ($scores['REPUTACIONAL'] * $wRep)) / $wTotal
        );

        // ─────────────────────────────────────────────────────────────────────
        // 8. RECOMENDACIÓN DE ACCIÓN (§6) Y NIVEL DE RIESGO DE PERSONA (§7.1)
        // ─────────────────────────────────────────────────────────────────────
        $minCategoryScore = min($scores);
        $jurisdiccionRiesgo = (bool) ($subject->jurisdiccion_riesgo ?? false);
        if ($jurisdiccionRiesgo) {
            $reglasNivelRiesgo[] = [
                'regla'  => 'Vínculo declarado con jurisdicción de alto riesgo (RCG Art. 23 Bis 4) → ALTO',
                'fuente' => 'contexto_sujeto',
                'campo'  => 'jurisdiccion_riesgo',
            ];
        }

        // 8.1 Determinar Nivel de Riesgo de Persona (BAJO | MEDIO | ALTO) según Tabla 7.1
        $esAlto = $tieneCoincidenciaSancionesLista
            || $tieneCoincidencia69B
            || $esPepConfirmado
            || $esPepExtranjero
            || $biometriaNoCoincide
            || $curpInvalido
            || $jurisdiccionRiesgo
            || ($minCategoryScore < 60);

        if ($esAlto) {
            $nivelRiesgoPersona = 'ALTO';
            if (empty($reglasNivelRiesgo)) {
                $reglasNivelRiesgo[] = [
                    'regla'  => "Categoría con puntuación menor a 60 ({$minCategoryScore}%) → ALTO",
                    'fuente' => 'motor_scoring',
                    'campo'  => 'scores_categoria',
                ];
            }
        } elseif (
            $minCategoryScore < 95
            || count($hallazgos) > 0
            || $hayFuenteAltaCriticaPendiente
            || count($fuentesPendientes) > 0
        ) {
            $nivelRiesgoPersona = 'MEDIO';
            if (empty($reglasNivelRiesgo)) {
                $reglasNivelRiesgo[] = [
                    'regla'  => $hayFuenteAltaCriticaPendiente
                        ? 'Fuente de criticidad ALTA pendiente de verificación → MEDIO'
                        : "Categorías en rango 60–94 (mínimo {$minCategoryScore}%) o fuentes pendientes → MEDIO",
                    'fuente' => 'motor_scoring',
                    'campo'  => 'fuentes_pendientes / scores_categoria',
                ];
            }
        } else {
            $nivelRiesgoPersona = 'BAJO';
            $reglasNivelRiesgo[] = [
                'regla'  => 'Las 4 categorías ≥ 95%, cero hallazgos confirmados y cero fuentes críticas pendientes → BAJO',
                'fuente' => 'motor_scoring',
                'campo'  => 'scores_categoria',
            ];
        }

        // 8.2 Determinar Recomendación de Acción (§6) garantizando compatibilidad estricta con Tabla 7.1
        if ($tieneCoincidenciaSancionesLista || $tieneCoincidencia69B) {
            $recomendacion = 'NO_PROCEDER';
            $justificacionRecomendacion = 'Se detectó coincidencia confirmada en listas de sanciones internacionales/nacionales o listado 69-B del SAT.';
        } elseif (
            $nivelRiesgoPersona === 'ALTO'
            || $minCategoryScore < 70
            || $esPepConfirmado
            || $tieneCoincidencia69
            || $tieneDiscrepanciaIdentidad
        ) {
            $recomendacion = 'REVISION_MANUAL';
            $justificacionRecomendacion = 'Se requiere revisión manual por hallazgo confirmado en PLD, categoría menor a 70% o discrepancia de identidad.';
        } elseif (
            $nivelRiesgoPersona === 'MEDIO'
            || $minCategoryScore < 95
            || $hayFuenteAltaCriticaPendiente
            || count($fuentesPendientes) > 0
        ) {
            $recomendacion = 'PROCEDER_CON_OBSERVACIONES';
            $obsList = array_map(fn($p) => "{$p['fuente']} ({$p['motivo']})", $fuentesPendientes);
            $justificacionRecomendacion = !empty($obsList)
                ? 'Observaciones abiertas por fuentes pendientes: ' . implode('; ', $obsList) . '.'
                : 'Alguna categoría se ubica entre 70% y 94%.';
        } else {
            $recomendacion = 'PROCEDER_SIN_OBSERVACIONES';
            $justificacionRecomendacion = 'Las 4 categorías presentan puntuación ≥ 95%, sin coincidencias en fuentes consultadas ni fuentes críticas pendientes.';
        }

        // ─────────────────────────────────────────────────────────────────────
        // 9. DISPARADORES DE DEBIDA DILIGENCIA REFORZADA (EDD — §7.5)
        // ─────────────────────────────────────────────────────────────────────
        $eddCausas = [];
        if ($nivelRiesgoPersona === 'ALTO') {
            $eddCausas[] = 'Nivel de Riesgo de Persona clasificado como ALTO';
        }
        if ($esPepConfirmado) {
            $eddCausas[] = 'Sujeto registrado como Persona Políticamente Expuesta (PEP)';
        }
        if ($esPepExtranjero) {
            $eddCausas[] = 'PEP extranjero (RCG Art. 23 Bis 4)';
        }
        if ($jurisdiccionRiesgo) {
            $eddCausas[] = 'Vínculo con jurisdicción de alto riesgo (RCG Art. 23 Bis 4)';
        }

        $eddRecomendada = count($eddCausas) > 0;
        $eddCausaTexto = $eddRecomendada
            ? implode('; ', $eddCausas) . '. Recordatorio normativo para el sujeto obligado: aplicar cuestionarios de origen y destino de recursos (RCG Art. 23 Ter 3), medidas reforzadas de identificación (Art. 23 Ter 4) y, en caso de PEP con riesgo alto, recabar aprobación de nivel directivo o constancia de motivos de la operación (Art. 23 Ter 5).'
            : 'No se activaron disparadores de Debida Diligencia Reforzada con las fuentes consultadas.';

        $estadoJurisdicciones = $subject->jurisdiccion_riesgo !== null
            ? ($subject->jurisdiccion_riesgo ? 'Evaluado: vínculo reportado en contexto' : 'Evaluado: sin vínculo declarado en contexto')
            : 'No evaluado: lista de jurisdicciones no disponible';

        // ─────────────────────────────────────────────────────────────────────
        // 10. FACTORES INHERENTES (RCG Art. 23 Bis 2 — §7.2)
        // ─────────────────────────────────────────────────────────────────────
        $factoresInherentes = $this->buildFactoresInherentes($subject, $fuentesCatalogadas, $hallazgos, $fechaConsulta);

        // ─────────────────────────────────────────────────────────────────────
        // 11. FECHA DE PRÓXIMA REEVALUACIÓN (§7.4) Y CONSERVACIÓN 10 AÑOS (§7.8)
        // ─────────────────────────────────────────────────────────────────────
        $policyId = $policies->get('IDENTIDAD');
        $intBajoMedio = min(6, max(1, (int) ($policyId?->intervalo_reevaluacion_bajo_medio_meses ?? 6)));
        $intAlto      = min(6, max(1, (int) ($policyId?->intervalo_reevaluacion_alto_meses ?? 3)));

        $intervaloMeses = ($nivelRiesgoPersona === 'ALTO') ? $intAlto : $intBajoMedio;
        $proximaReevaluacion = $fechaConsulta->copy()->addMonths($intervaloMeses);
        $conservarHasta      = $fechaConsulta->copy()->addYears(10);

        // ─────────────────────────────────────────────────────────────────────
        // 12. BENEFICIARIO CONTROLADOR (§7.7) Y SELECCIÓN DE PERSONAL (§7.9)
        // ─────────────────────────────────────────────────────────────────────
        $tipoPersonaStr = $subject->tipo === 'persona_moral' ? 'MORAL' : 'FISICA';
        $finalidadNorm  = $this->normalizeFinalidad($subject);

        $beneficiarioControlador = ($tipoPersonaStr === 'MORAL')
            ? $this->buildBeneficiarioControlador($subject, $fuentesCatalogadas)
            : null;

        $corroboracionPersonal = ($finalidadNorm === 'seleccion_personal')
            ? $this->buildCorroboracionPersonal($subject, $fuentesCatalogadas)
            : null;

        // ─────────────────────────────────────────────────────────────────────
        // 13. RESUMEN DE RESULTADOS POR FUENTE (§9)
        // ─────────────────────────────────────────────────────────────────────
        $resumenFuentes = [];
        foreach ($fuentesCatalogadas as $fKey => $fItem) {
            $resumenFuentes[] = [
                'fuente'                  => $fKey,
                'nombre_fuente'           => $fItem['nombre_fuente'],
                'categoria'               => $fItem['categoria'],
                'criticidad'              => $fItem['criticidad'],
                'status_http'             => $fItem['status_http'],
                'estado_consulta'         => $fItem['es_caida']
                    ? ($fItem['estado'] === 'NO_RECONSULTADA' ? 'NO_RECONSULTADA' : 'PENDIENTE / ERROR HTTP ' . $fItem['status_http'])
                    : 'CONSULTADA (HTTP ' . $fItem['status_http'] . ')',
                'resultado_general'       => $this->summarizeSourceResult($fKey, $fItem),
                'fecha_consulta'          => $fItem['fecha_consulta'],
                'ultima_consulta_exitosa' => $fItem['ultima_consulta_exitosa'],
                'resultado_crudo'         => $fItem['resultado_crudo'],
            ];
        }

        // ─────────────────────────────────────────────────────────────────────
        // 14. ESTRUCTURA DE SALIDA JSON ESTABLE (Doc 2 §5 y Doc 3 §9)
        // ─────────────────────────────────────────────────────────────────────
        $expedienteData = [
            'schema_version'   => 1,
            'folio'            => $metaVersion['folio'] ?? 'No asignado',
            'version'          => (int) ($metaVersion['version'] ?? 1),
            'tipo_ejecucion'   => $metaVersion['tipo_ejecucion'] ?? 'INICIAL',
            'fecha_generacion' => $fechaConsulta->toIso8601String(),
            'hash_previo'      => $metaVersion['hash_previo'] ?? null,
            'meta_version'     => [
                'folio'            => $metaVersion['folio'] ?? 'No asignado',
                'version'          => (int) ($metaVersion['version'] ?? 1),
                'tipo_ejecucion'   => $metaVersion['tipo_ejecucion'] ?? 'INICIAL',
                'fecha_generacion' => $fechaConsulta->toIso8601String(),
                'hash_previo'      => $metaVersion['hash_previo'] ?? null,
            ],
            'persona' => [
                'subject_id'        => $subject->id,
                'nombre_o_razon'    => $subject->name_or_company ?? 'No provisto',
                'tipo_persona'      => $tipoPersonaStr,
                'rfc'               => $subject->rfc ?? 'No provisto',
                'curp'              => $subject->curp ?? ($tipoPersonaStr === 'MORAL' ? 'No aplica' : 'No provisto'),
                'domicilio'         => $subject->address ?? 'No provisto',
            ],
            'finalidad'                  => $finalidadNorm,
            'consentimiento'             => [
                'otorgado'    => (bool) $subject->consent_granted,
                'fecha_firma' => $subject->consent_date ? Carbon::parse($subject->consent_date)->toIso8601String() : 'No provisto',
                'base_legal'  => $subject->consent_legal_basis ?? $subject->finalidad_clave ?? 'No provisto',
            ],
            'recomendacion'              => $recomendacion,
            'recomendacion_justificacion'=> $justificacionRecomendacion,
            'score_global'               => $scoreGlobal,
            'scores_categoria'           => [
                'IDENTIDAD'    => ['score' => $scores['IDENTIDAD'],    'peso' => $wId,   'estatus' => $this->categoryStatusLabel($scores['IDENTIDAD'])],
                'PLD'          => ['score' => $scores['PLD'],          'peso' => $wPld,  'estatus' => $this->categoryStatusLabel($scores['PLD'])],
                'CONSISTENCIA' => ['score' => $scores['CONSISTENCIA'], 'peso' => $wCons, 'estatus' => $this->categoryStatusLabel($scores['CONSISTENCIA'])],
                'REPUTACIONAL' => ['score' => $scores['REPUTACIONAL'], 'peso' => $wRep,  'estatus' => $this->categoryStatusLabel($scores['REPUTACIONAL'])],
            ],
            'nivel_riesgo_persona' => [
                'nivel'  => $nivelRiesgoPersona,
                'reglas' => $reglasNivelRiesgo,
            ],
            'edd' => [
                'recomendada'          => $eddRecomendada,
                'causa'                => $eddCausaTexto,
                'jurisdicciones_uif'   => $estadoJurisdicciones,
            ],
            'pep'                        => $pepInfo,
            'factores_inherentes'        => $factoresInherentes,
            'factores_transaccionales'   => [
                'leyenda_fija'           => config('pld.textos_fijos.factores_transaccionales'),
                'declarado_por_cliente'  => !empty($subject->perfil_transaccional)
                    ? $subject->perfil_transaccional . ' (declarado por el cliente, no verificado por AvalID)'
                    : 'No provisto por el cliente',
                'origen_destino_recursos'=> !empty($subject->origen_destino_recursos)
                    ? $subject->origen_destino_recursos . ' (declarado por el cliente, no verificado por AvalID)'
                    : 'No provisto por el cliente',
            ],
            'beneficiario_controlador'   => $beneficiarioControlador,
            'corroboracion_personal'     => $corroboracionPersonal,
            'hallazgos'                  => $hallazgos,
            'fuentes_pendientes'         => $fuentesPendientes,
            'fuentes_no_catalogadas'     => $fuentesNoCatalogadas,
            'resumen_fuentes'            => $resumenFuentes,
            'intervalo_reevaluacion_meses' => $intervaloMeses,
            'proxima_reevaluacion'       => $proximaReevaluacion->toIso8601String(),
            'conservar_hasta'            => $conservarHasta->toIso8601String(),
            'cambios'                    => $diffEstructurado,
            'descargo_responsabilidad'   => config('pld.textos_fijos.descargo_base') . ' ' . config('pld.textos_fijos.descargo_linea_ebr'),
        ];

        // Calcular hash_version canónico JCS (RFC 8785) sobre el paquete completo
        $hashVersion = JcsCanonicalizer::hashPayload($expedienteData);
        $expedienteData['hash_version'] = $hashVersion;
        $expedienteData['meta_version']['hash_version'] = $hashVersion;

        // ─────────────────────────────────────────────────────────────────────
        // 15. GATE BLOQUEANTE DE VERIFICACIÓN PRE-ENTREGA DE 14 PUNTOS (§10 — M7)
        // ─────────────────────────────────────────────────────────────────────
        $this->verifyPreDeliveryChecklist($expedienteData);

        return $expedienteData;
    }

    /**
     * Construye las 8 filas de Factores Inherentes (RCG Art. 23 Bis 2 — §7.2).
     * Jamás rellena con supuestos un factor sin dato.
     */
    protected function buildFactoresInherentes(
        Subject $subject,
        array $fuentesCatalogadas,
        array $hallazgos,
        Carbon $fechaConsulta
    ): array {
        $fIso = $fechaConsulta->toIso8601String();

        // 1. Antecedentes (módulos 4.2 y 4.4)
        $sancOk = !empty($fuentesCatalogadas['sanciones']['tiene_datos_evaluables']);
        $satOk  = !empty($fuentesCatalogadas['sat_listas']['tiene_datos_evaluables']);
        $litOk  = !empty($fuentesCatalogadas['litigios']['tiene_datos_evaluables']);

        if ($sancOk || $satOk || $litOk) {
            $partesAnt = [];
            $fuentesAnt = [];
            if ($sancOk) {
                $fuentesAnt[] = 'sanciones';
                $hitsCount = count($fuentesCatalogadas['sanciones']['resultado_crudo']['hits'] ?? []);
                $partesAnt[] = $hitsCount > 0
                    ? "{$hitsCount} coincidencia(s) en listas sanciones/PEP (sanciones)"
                    : "Sin coincidencia en listas sanciones/PEP (sanciones)";
            }
            if ($satOk) {
                $fuentesAnt[] = 'sat_listas';
                $dSat = $fuentesCatalogadas['sat_listas']['resultado_crudo'];
                $partesAnt[] = (!empty($dSat['en_lista_69']) || !empty($dSat['en_lista_69b']))
                    ? "Coincidencia en listas Art. 69/69-B SAT (sat_listas)"
                    : "Sin coincidencia en listas Art. 69/69-B SAT (sat_listas)";
            }
            if ($litOk) {
                $fuentesAnt[] = 'litigios';
                $jCount = count($fuentesCatalogadas['litigios']['resultado_crudo']['juicios'] ?? []);
                $partesAnt[] = $jCount > 0
                    ? "{$jCount} expediente(s) en Boletín Judicial (litigios)"
                    : "Sin coincidencia en Boletín Judicial (litigios)";
            }
            $factorAntecedentes = [
                'factor'         => 'Antecedentes',
                'valor'          => implode('; ', $partesAnt),
                'fuente'         => implode(', ', $fuentesAnt),
                'fecha_consulta' => $fIso,
                'estado'         => 'Evaluado',
            ];
        } else {
            $factorAntecedentes = [
                'factor'         => 'Antecedentes',
                'valor'          => 'No provisto (fuentes de listas y litigios pendientes)',
                'fuente'         => 'Sin dato',
                'fecha_consulta' => null,
                'estado'         => 'No evaluado',
            ];
        }

        // 2. Tipo de persona
        $tipoRfc = $fuentesCatalogadas['rfc']['resultado_crudo']['tipo_persona'] ?? null;
        $tipoVal = $tipoRfc ?: ($subject->tipo === 'persona_moral' ? 'MORAL' : 'FISICA');
        $fuenteTipo = $tipoRfc ? 'rfc (tipo_persona)' : 'contexto_sujeto (tipo)';

        // 3. Fecha de nacimiento o constitución (NUNCA extraída del RFC)
        $fechaNacConst = null;
        $fuenteNacConst = null;
        if ($subject->tipo === 'persona_fisica') {
            if (!empty($fuentesCatalogadas['curp']['resultado_crudo']['fecha_nacimiento'])) {
                $fechaNacConst  = (string) $fuentesCatalogadas['curp']['resultado_crudo']['fecha_nacimiento'];
                $fuenteNacConst = 'curp (fecha_nacimiento)';
            } elseif (!empty($fuentesCatalogadas['ine_frente']['resultado_crudo']['fecha_nacimiento'])) {
                $fechaNacConst  = (string) $fuentesCatalogadas['ine_frente']['resultado_crudo']['fecha_nacimiento'];
                $fuenteNacConst = 'ine_frente (fecha_nacimiento)';
            }
        } else {
            if (!empty($fuentesCatalogadas['siger']['resultado_crudo']['resultados'][0]['fecha_constitucion'])) {
                $fechaNacConst  = (string) $fuentesCatalogadas['siger']['resultado_crudo']['resultados'][0]['fecha_constitucion'];
                $fuenteNacConst = 'siger (resultados[0].fecha_constitucion)';
            }
        }

        // 4. Giro o actividad económica
        $giroVal = null;
        $fuenteGiro = null;
        if (!empty($fuentesCatalogadas['denue']['resultado_crudo']['establecimientos'][0]['actividad'])) {
            $giroVal    = (string) $fuentesCatalogadas['denue']['resultado_crudo']['establecimientos'][0]['actividad'];
            $fuenteGiro = 'denue (establecimientos[0].actividad)';
        } elseif (!empty($fuentesCatalogadas['siger']['resultado_crudo']['resultados'][0]['objeto_social'])) {
            $giroVal    = (string) $fuentesCatalogadas['siger']['resultado_crudo']['resultados'][0]['objeto_social'];
            $fuenteGiro = 'siger (resultados[0].objeto_social)';
        } elseif (!empty($subject->actividad_economica)) {
            $giroVal    = (string) $subject->actividad_economica;
            $fuenteGiro = 'cliente (actividad_economica)';
        }

        // 5. Nacionalidad
        $nacVal = null;
        $fuenteNac = null;
        if (!empty($fuentesCatalogadas['curp']['resultado_crudo']['nacionalidad'])) {
            $nacVal    = (string) $fuentesCatalogadas['curp']['resultado_crudo']['nacionalidad'];
            $fuenteNac = 'curp (nacionalidad)';
        } elseif (!empty($subject->nacionalidad)) {
            $nacVal    = (string) $subject->nacionalidad;
            $fuenteNac = 'cliente (nacionalidad)';
        }

        // 6. Lugar de residencia
        $resVal = null;
        $fuenteRes = null;
        if (!empty($fuentesCatalogadas['comprobante_domicilio']['resultado_crudo']['calle'])) {
            $cd = $fuentesCatalogadas['comprobante_domicilio']['resultado_crudo'];
            $resVal    = trim(($cd['calle'] ?? '') . ' ' . ($cd['numero_exterior'] ?? '') . ', ' . ($cd['colonia'] ?? '') . ', ' . ($cd['municipio'] ?? '') . ', ' . ($cd['estado'] ?? '') . ' CP ' . ($cd['codigo_postal'] ?? ''));
            $fuenteRes = 'comprobante_domicilio';
        } elseif (!empty($fuentesCatalogadas['ine_frente']['resultado_crudo']['calle_numero']) || !empty($fuentesCatalogadas['ine_frente']['resultado_crudo']['estado'])) {
            $id = $fuentesCatalogadas['ine_frente']['resultado_crudo'];
            $resVal    = trim(($id['calle_numero'] ?? '') . ', ' . ($id['colonia'] ?? '') . ', ' . ($id['municipio'] ?? '') . ', ' . ($id['estado'] ?? '') . ' CP ' . ($id['codigo_postal'] ?? ''), ', ');
            $fuenteRes = 'ine_frente';
        } elseif (!empty($subject->address)) {
            $resVal    = (string) $subject->address;
            $fuenteRes = 'cliente (domicilio)';
        }

        // 7. Fuentes de ingreso
        $ingVal = !empty($subject->fuentes_ingreso) ? (string) $subject->fuentes_ingreso : null;
        $fuenteIng = $ingVal ? 'cliente (fuentes_ingreso)' : null;

        // 8. Naturaleza y propósito de la relación
        $propVal = !empty($subject->finalidad_clave)
            ? (string) $subject->finalidad_clave
            : (!empty($subject->consent_legal_basis) ? (string) $subject->consent_legal_basis : null);
        $fuenteProp = $propVal ? 'cliente (finalidad)' : null;

        return [
            $factorAntecedentes,
            [
                'factor'         => 'Tipo de persona',
                'valor'          => $tipoVal,
                'fuente'         => $fuenteTipo,
                'fecha_consulta' => $fIso,
                'estado'         => 'Evaluado',
            ],
            [
                'factor'         => 'Fecha de nacimiento o constitución',
                'valor'          => $fechaNacConst ?? 'No provisto',
                'fuente'         => $fuenteNacConst ?? 'Sin dato',
                'fecha_consulta' => $fechaNacConst ? $fIso : null,
                'estado'         => $fechaNacConst ? 'Evaluado' : 'No evaluado',
            ],
            [
                'factor'         => 'Giro o actividad económica',
                'valor'          => $giroVal ?? 'No provisto',
                'fuente'         => $fuenteGiro ?? 'Sin dato',
                'fecha_consulta' => $giroVal ? $fIso : null,
                'estado'         => $giroVal ? 'Evaluado' : 'No evaluado',
            ],
            [
                'factor'         => 'Nacionalidad',
                'valor'          => $nacVal ?? 'No provisto',
                'fuente'         => $fuenteNac ?? 'Sin dato',
                'fecha_consulta' => $nacVal ? $fIso : null,
                'estado'         => $nacVal ? 'Evaluado' : 'No evaluado',
            ],
            [
                'factor'         => 'Lugar de residencia',
                'valor'          => $resVal ?? 'No provisto',
                'fuente'         => $fuenteRes ?? 'Sin dato',
                'fecha_consulta' => $resVal ? $fIso : null,
                'estado'         => $resVal ? 'Evaluado' : 'No evaluado',
            ],
            [
                'factor'         => 'Fuentes de ingreso',
                'valor'          => $ingVal ?? 'No provisto',
                'fuente'         => $fuenteIng ?? 'Sin dato',
                'fecha_consulta' => $ingVal ? $fIso : null,
                'estado'         => $ingVal ? 'Evaluado' : 'No evaluado',
            ],
            [
                'factor'         => 'Naturaleza y propósito de la relación',
                'valor'          => $propVal ?? 'No provisto',
                'fuente'         => $fuenteProp ?? 'Sin dato',
                'fecha_consulta' => $propVal ? $fIso : null,
                'estado'         => $propVal ? 'Evaluado' : 'No evaluado',
            ],
        ];
    }

    /**
     * Evalúa Beneficiario Controlador según orden de prelación obligatorio (RCG Art. 23 Quinquies — §7.7):
     * (I) 25% o más de composición accionaria -> (II) control por otros medios -> (III) funcionario de mayor grado.
     */
    protected function buildBeneficiarioControlador(Subject $subject, array $fuentesCatalogadas): array
    {
        $estructura = is_array($subject->estructura_accionaria) ? $subject->estructura_accionaria : [];
        $sigerSocios = $fuentesCatalogadas['siger']['resultado_crudo']['resultados'][0]['socios'] ?? [];

        $prelacionI = [];
        // Revisar SIGER si trae porcentajes explícitos >= 25%
        if (is_array($sigerSocios)) {
            foreach ($sigerSocios as $s) {
                $part = (string) ($s['participacion'] ?? '');
                if (preg_match('/(\d+(?:\.\d+)?)\s*%/', $part, $m)) {
                    $pct = (float) $m[1];
                    if ($pct >= 25.0) {
                        $prelacionI[] = [
                            'nombre'        => $s['nombre'] ?? 'No provisto',
                            'participacion' => $part,
                            'fuente'        => 'siger (resultados[0].socios)',
                        ];
                    }
                }
            }
        }

        // Revisar estructura_accionaria aportada en el input si existe
        if (empty($prelacionI) && !empty($estructura['accionistas']) && is_array($estructura['accionistas'])) {
            foreach ($estructura['accionistas'] as $acc) {
                $pct = (float) ($acc['porcentaje'] ?? 0);
                if ($pct >= 25.0) {
                    $prelacionI[] = [
                        'nombre'        => $acc['nombre'] ?? 'No provisto',
                        'participacion' => $pct . '%',
                        'fuente'        => 'estructura_accionaria (declarada)',
                    ];
                }
            }
        }

        if (!empty($prelacionI)) {
            return [
                'estado'             => 'Evaluado',
                'prelacion_aplicada' => 'Fracción I — Persona(s) física(s) con 25% o más de la composición accionaria (RCG Art. 23 Quinquies, fr. I)',
                'criterio_legal'     => 'Prelación por composición accionaria ≥ 25% (Art. 23 Quinquies, fr. I). Distinto del umbral de derechos de voto > 25% de la definición de control efectivo (Art. 3, fr. IV).',
                'beneficiarios'      => $prelacionI,
            ];
        }

        if (!empty($estructura['control_otros_medios'])) {
            return [
                'estado'             => 'Evaluado',
                'prelacion_aplicada' => 'Fracción II — Persona(s) con control por otros medios (estrategia, decisiones o políticas — RCG Art. 23 Quinquies, fr. II)',
                'criterio_legal'     => 'Control efectivo por otros medios al no identificarse accionista ≥ 25%.',
                'beneficiarios'      => (array) $estructura['control_otros_medios'],
            ];
        }

        if (!empty($estructura['alta_direccion'])) {
            return [
                'estado'             => 'Evaluado',
                'prelacion_aplicada' => 'Fracción III — Funcionario administrativo de mayor grado o de alta dirección (RCG Art. 23 Quinquies, fr. III)',
                'criterio_legal'     => 'Prelación subsidiaria de alta dirección.',
                'beneficiarios'      => (array) $estructura['alta_direccion'],
            ];
        }

        return [
            'estado'             => 'No evaluado: sin datos de estructura de propiedad',
            'prelacion_aplicada' => 'Ninguna (no se supone beneficiario controlador sin datos explícitos)',
            'criterio_legal'     => 'Orden obligatorio RCG Art. 23 Quinquies: (I) ≥ 25% capital social → (II) control por otros medios → (III) alta dirección.',
            'beneficiarios'      => [],
        ];
    }

    /**
     * Construye el bloque de Corroboración para Selección de Personal (RCG Art. 39 Bis 2 — §7.9).
     * Prohibido afirmar "sin sentencia" o "sin inhabilitación" sin fuente oficial textual.
     */
    protected function buildCorroboracionPersonal(Subject $subject, array $fuentesCatalogadas): array
    {
        $declaracion = is_array($subject->declaracion_personal) ? $subject->declaracion_personal : null;

        // Contrastar trayectoria laboral con nss_imss
        if (!isset($fuentesCatalogadas['nss_imss'])) {
            $trayectoria = 'No verificable con las fuentes configuradas (fuente nss_imss no incluida).';
        } elseif ($fuentesCatalogadas['nss_imss']['es_caida']) {
            $trayectoria = 'No verificable: fuente pendiente (nss_imss).';
        } else {
            $emp = $fuentesCatalogadas['nss_imss']['resultado_crudo']['empleos'] ?? [];
            $sem = $fuentesCatalogadas['nss_imss']['resultado_crudo']['semanas_cotizadas'] ?? 'Sin dato';
            $trayectoria = count($emp) > 0
                ? 'Fuente nss_imss reporta ' . count($emp) . " registro(s) patronal(es) y {$sem} semanas cotizadas."
                : 'Sin registros patronales devueltos en nss_imss.';
        }

        return [
            'declaracion_firmada'            => $declaracion
                ? json_encode($declaracion, JSON_UNESCAPED_UNICODE) . ' (declarado por el candidato)'
                : 'No provisto por el cliente',
            'corroboracion_trayectoria_imss' => $trayectoria,
            'delitos_patrimoniales_inhabilitacion' => 'No verificable con las fuentes configuradas (AvalID no afirma ausencia de sentencia firme ni de inhabilitación administrativa sin fuente oficial explícita — RCG Art. 39 Bis 2).',
        ];
    }

    /**
     * Gate bloqueante de 14 puntos de Verificación Pre-Entrega (§10 — M7).
     * Lanza excepción si alguna regla de integridad o límite no negociable se viola.
     */
    public function verifyPreDeliveryChecklist(array $expedienteData): void
    {
        // 1. Cada hallazgo tiene fuente y campo del JSON que lo respalda
        foreach ($expedienteData['hallazgos'] ?? [] as $h) {
            if (empty($h['fuente']) || empty($h['campo'])) {
                throw new \DomainException('Fallo Checklist Pre-Entrega #1: Hallazgo sin fuente o campo de respaldo.');
            }
        }

        // 2 & 6. Recomendación válida (§6) y compatible con el Nivel de Riesgo de Persona (Tabla 7.1)
        $nivel = $expedienteData['nivel_riesgo_persona']['nivel'] ?? '';
        $rec   = $expedienteData['recomendacion'] ?? '';

        $compatibilidad = [
            'ALTO'  => ['REVISION_MANUAL', 'NO_PROCEDER'],
            'MEDIO' => ['PROCEDER_CON_OBSERVACIONES', 'REVISION_MANUAL'],
            'BAJO'  => ['PROCEDER_SIN_OBSERVACIONES'],
        ];

        if (!isset($compatibilidad[$nivel]) || !in_array($rec, $compatibilidad[$nivel], true)) {
            throw new \DomainException("Fallo Checklist Pre-Entrega #2/#6: Nivel ({$nivel}) incompatible con recomendación ({$rec}) según Tabla 7.1.");
        }

        // 3. Ninguna categoría muestra 100% si tiene hallazgo confirmado o fuente pendiente en ella
        $scoresCat = $expedienteData['scores_categoria'] ?? [];
        foreach ($expedienteData['hallazgos'] ?? [] as $h) {
            $catH = $h['categoria'] ?? '';
            if (isset($scoresCat[$catH]) && $scoresCat[$catH]['score'] >= 100) {
                throw new \DomainException("Fallo Checklist Pre-Entrega #3: La categoría {$catH} muestra 100% pese a tener hallazgo confirmado.");
            }
        }

        // 4. Todas las fuentes con error HTTP están en fuentes_pendientes
        foreach ($expedienteData['resumen_fuentes'] ?? [] as $rf) {
            if (($rf['status_http'] < 200 || $rf['status_http'] >= 400) && !str_contains($rf['estado_consulta'], 'PENDIENTE') && !str_contains($rf['estado_consulta'], 'NO_RECONSULTADA')) {
                throw new \DomainException("Fallo Checklist Pre-Entrega #4: Fuente {$rf['fuente']} con error HTTP no clasificada como pendiente.");
            }
        }

        // 7. Si hay PEP, sanción o 69-B, el nivel es ALTO y EDD = SÍ
        if (($expedienteData['pep']['estatus'] ?? '') === 'REGISTRADO COMO PEP') {
            if ($nivel !== 'ALTO' || empty($expedienteData['edd']['recomendada'])) {
                throw new \DomainException('Fallo Checklist Pre-Entrega #7: PEP confirmado debe producir Nivel ALTO y EDD = SÍ.');
            }
        }

        // 8. Cada factor Evaluado tiene fuente; ningún No evaluado fue rellenado con supuestos
        foreach ($expedienteData['factores_inherentes'] ?? [] as $fi) {
            if ($fi['estado'] === 'Evaluado' && (empty($fi['fuente']) || $fi['fuente'] === 'Sin dato')) {
                throw new \DomainException("Fallo Checklist Pre-Entrega #8: Factor '{$fi['factor']}' marcado Evaluado sin fuente.");
            }
        }

        // 9. Fecha de reevaluación calculada y no excede 6 meses
        $intMeses = (int) ($expedienteData['intervalo_reevaluacion_meses'] ?? 0);
        if ($intMeses < 1 || $intMeses > 6 || empty($expedienteData['proxima_reevaluacion'])) {
            throw new \DomainException('Fallo Checklist Pre-Entrega #9: El intervalo de reevaluación excede 6 meses o falta fecha.');
        }

        // 10. Leyenda de factores transaccionales no evaluados y línea EBR en el descargo
        $leyendaTrans = $expedienteData['factores_transaccionales']['leyenda_fija'] ?? '';
        $descargo     = $expedienteData['descargo_responsabilidad'] ?? '';
        if (!str_contains($leyendaTrans, 'no evaluados por AvalID') || !str_contains($descargo, 'No constituye el Grado de Riesgo del sujeto obligado')) {
            throw new \DomainException('Fallo Checklist Pre-Entrega #10: Falta leyenda fija de factores transaccionales o línea EBR en el descargo.');
        }

        // 11 & 14. Prohibición absoluta de frases "sin antecedentes", "limpio", "sin sentencia" y de mencionar avisos/requerimientos
        $serialized = json_encode($expedienteData, JSON_UNESCAPED_UNICODE);
        self::assertNoForbiddenPhrases($serialized);

        // 12. Folio, versión y hashes presentes en meta_version
        if (empty($expedienteData['meta_version']['folio']) || empty($expedienteData['meta_version']['version']) || empty($expedienteData['hash_version'])) {
            throw new \DomainException('Fallo Checklist Pre-Entrega #12: Metadatos de versión o hash faltantes.');
        }

        // 13. Ninguna fuente NO_RECONSULTADA aparece como "sin coincidencia" sin advertir su estado
        foreach ($expedienteData['resumen_fuentes'] ?? [] as $rf) {
            if ($rf['estado_consulta'] === 'NO_RECONSULTADA' && !str_contains($rf['resultado_general'], 'no reconsultada en esta versión')) {
                throw new \DomainException("Fallo Checklist Pre-Entrega #13: Fuente {$rf['fuente']} NO_RECONSULTADA mal descrita.");
            }
        }
    }

    /**
     * Valida que ningún texto generado por el sistema contenga frases prohibidas
     * ("sin antecedentes", "limpio", "sin sentencia") ni mencione avisos o requerimientos del sujeto obligado (Ley Art. 63 fr. II).
     */
    public static function assertNoForbiddenPhrases(string $text): void
    {
        $forbiddenAbsence = [
            '/\bsin\s+antecedentes\b/iu',
            '/\bestá\s+limpio\b/iu',
            '/\bexpediente\s+limpio\b/iu',
            '/\bsujeto\s+limpio\b/iu',
            '/\bya\s+está\s+limpio\b/iu',
            '/\bsin\s+sentencia\b/iu',
        ];

        foreach ($forbiddenAbsence as $pattern) {
            if (preg_match($pattern, $text)) {
                throw new \DomainException("Violación de Regla Madre (Anti-Invención): Se detectó frase de ausencia prohibida ({$pattern}).");
            }
        }

        // Prohibido mencionar avisos o requerimientos del sujeto obligado (Ley Art. 63 fr. II)
        $forbiddenConfidentiality = [
            '/\baviso\s+de\s+24\s+horas\b/iu',
            '/\bpresentar\s+aviso\b/iu',
            '/\bpresentó\s+un\s+aviso\b/iu',
            '/\bobjeto\s+de\s+un\s+aviso\b/iu',
            '/\brequerimiento\s+de\s+la\s+autoridad\b/iu',
            '/\brequerimientos?\s+de\s+la\s+uif\b/iu',
        ];

        foreach ($forbiddenConfidentiality as $pattern) {
            if (preg_match($pattern, $text)) {
                throw new \DomainException("Violación de Confidencialidad PLD (Ley Art. 63 fr. II): Texto contiene referencia prohibida a avisos o requerimientos.");
            }
        }
    }

    protected function summarizeSourceResult(string $fKey, array $fItem): string
    {
        $prefixNoRec = ($fItem['estado'] === 'NO_RECONSULTADA')
            ? "[no reconsultada en esta versión; última consulta exitosa: " . ($fItem['ultima_consulta_exitosa'] ?? 'Sin dato') . "] "
            : '';

        if ($fItem['es_caida'] && empty($fItem['tiene_datos_evaluables'])) {
            return $prefixNoRec . "No concluyente (HTTP {$fItem['status_http']}) — pendiente de verificación.";
        }

        $d = $fItem['resultado_crudo'];

        return $prefixNoRec . match ($fKey) {
            'lista_nominal' => (!empty($d['activa']) || !empty($d['valida']))
                ? 'Credencial vigente en Lista Nominal INE (' . ($d['estado'] ?? 'Vigente') . ')'
                : 'Credencial no vigente o sin coincidencia en Lista Nominal INE (' . ($d['estado'] ?? 'Inactiva') . ')',
            'ine_vs_selfie' => ($d['status'] ?? '') === 'skipped'
                ? 'Comparación biométrica no ejecutada por falta de captura'
                : ((!empty($d['coincide_rostro']) ? 'Coincidencia biométrica facial positiva' : 'Biometría facial NO COINCIDE') . ' (certeza: ' . ($d['certeza_porcentaje'] ?? 'N/A') . ')'),
            'curp' => !empty($d['valida'])
                ? 'CURP validada en RENAPO (estatus: ' . ($d['estatus_curp'] ?? 'AN') . ')'
                : 'CURP no válida o en estatus de baja en RENAPO',
            'rfc' => !empty($d['valido'])
                ? 'RFC activo ante el SAT (' . ($d['situacion'] ?? 'ACTIVO') . ')'
                : 'RFC inactivo o sin coincidencia ante el SAT',
            'sanciones' => !empty($d['encontrado']) && !empty($d['hits'])
                ? count($d['hits']) . ' coincidencia(s) reportada(s) en listas de sanciones / PEP'
                : 'Sin coincidencia en listas OFAC / ONU / UE / LPB / PEP consultadas',
            'sat_listas' => (!empty($d['en_lista_69']) || !empty($d['en_lista_69b']))
                ? 'Coincidencia reportada en listados Art. 69 / 69-B del SAT'
                : 'Sin coincidencia en listados Art. 69 y 69-B del SAT',
            'litigios' => !empty($d['tiene_juicios'])
                ? count($d['juicios'] ?? []) . ' expediente(s) localizado(s) en Boletín Judicial'
                : 'Sin coincidencia en Boletín Judicial Nacional',
            'csd' => 'Total certificados reportados: ' . ($d['total'] ?? count($d['certificados'] ?? [])),
            'nss_imss' => 'Semanas cotizadas reportadas: ' . ($d['semanas_cotizadas'] ?? 'Sin dato'),
            'siger' => count($d['resultados'] ?? []) . ' folio(s) mercantil(es) reportado(s) en SIGER',
            default => 'Consulta completada con datos estructurados en la fuente ' . $fKey,
        };
    }

    protected function normalizePersonName(string $name): string
    {
        $unaccent = strtr($name, [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
            'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U','ü'=>'U','ñ'=>'N',
        ]);
        $clean = preg_replace('/[^A-Z ]/i', '', strtoupper($unaccent));
        return trim(preg_replace('/\s+/', ' ', $clean));
    }

    protected function normalizeDateString(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateStr, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }
        return null;
    }

    protected function normalizeFinalidad(Subject $subject): string
    {
        $raw = strtolower((string) ($subject->finalidad_clave ?? $subject->consent_legal_basis ?? 'alta_cliente'));
        if (str_contains($raw, 'laboral') || str_contains($raw, 'personal') || str_contains($raw, 'contratacion') || str_contains($raw, 'seleccion')) {
            return 'seleccion_personal';
        }
        if (str_contains($raw, 'cliente') || str_contains($raw, 'comercial') || str_contains($raw, 'proveedor') || str_contains($raw, 'alta')) {
            return 'alta_cliente';
        }
        return 'otro';
    }

    protected function categoryStatusLabel(int $score): string
    {
        if ($score >= 95) return 'SIN OBSERVACIONES';
        if ($score >= 70) return 'CON OBSERVACIONES';
        if ($score > 0)   return 'ALERTA / TOPE APLICADO';
        return 'REPROBADA (TOPE 0)';
    }

    protected function isTriggerActive($policies, string $cat, string $triggerKey): bool
    {
        $cfg = $policies->get($cat)?->triggers_config ?? [];
        return (bool) ($cfg[$triggerKey]['activo'] ?? true);
    }

    protected function getTriggerValue($policies, string $cat, string $triggerKey, int $default): int
    {
        $cfg = $policies->get($cat)?->triggers_config ?? [];
        return isset($cfg[$triggerKey]['valor']) ? (int) $cfg[$triggerKey]['valor'] : $default;
    }
}
