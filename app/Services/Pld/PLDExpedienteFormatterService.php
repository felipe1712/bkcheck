<?php

namespace App\Services\Pld;

/**
 * Formateador Determinístico Basado en Plantillas para el Expediente PLD/EBR (M6 — Doc 3 §9).
 *
 * Respeta el orden y encabezados EXACTOS del Documento 3 §9:
 * 1. INFORMACIÓN GENERAL DEL SUJETO
 * 2. RECOMENDACIÓN DE ACCIÓN
 * 3. EVALUACIÓN MULTÍMETRO POR CATEGORÍAS
 * 4. NIVEL DE RIESGO DE PERSONA (INSUMO EBR)
 * 5. CONTROL DE VERSIÓN
 * 6. CAMBIOS RESPECTO A LA VERSIÓN ANTERIOR (solo en reevaluaciones)
 * 7. HALLAZGOS CONFIRMADOS (N)
 * 8. FUENTES PENDIENTES DE VERIFICACIÓN (NO CONCLUYENTES)
 * 9. CONSENTIMIENTO LEGAL Y CUMPLIMIENTO (LFPDPPP)
 * 10. RESUMEN DE RESULTADOS POR FUENTES
 * 11. DETALLE POR FUENTE
 * 12. DESCARGO DE RESPONSABILIDAD
 */
class PLDExpedienteFormatterService
{
    /**
     * Genera la representación textual estructurada exacta conforme a Documento 3 §9.
     */
    public function formatText(array $exp): string
    {
        $lines = [];
        $persona = $exp['persona'] ?? [];
        $scoresCat = $exp['scores_categoria'] ?? [];
        $ebr = $exp['nivel_riesgo_persona'] ?? [];
        $edd = $exp['edd'] ?? [];
        $pep = $exp['pep'] ?? [];
        $meta = $exp['meta_version'] ?? [];
        $hallazgos = $exp['hallazgos'] ?? [];
        $pendientes = $exp['fuentes_pendientes'] ?? [];
        $consent = $exp['consentimiento'] ?? [];

        // 1. INFORMACIÓN GENERAL DEL SUJETO
        $lines[] = 'INFORMACIÓN GENERAL DEL SUJETO';
        $lines[] = sprintf(
            '%s | %s | RFC: %s | CURP: %s | Domicilio: %s',
            $persona['nombre_o_razon'] ?? 'No provisto',
            $persona['tipo_persona'] ?? 'No provisto',
            $persona['rfc'] ?? 'No provisto',
            $persona['curp'] ?? 'No provisto',
            $persona['domicilio'] ?? 'No provisto'
        );
        $lines[] = '';

        // 2. RECOMENDACIÓN DE ACCIÓN
        $lines[] = 'RECOMENDACIÓN DE ACCIÓN: ' . ($exp['recomendacion'] ?? 'REVISION_MANUAL');
        $lines[] = $exp['recomendacion_justificacion'] ?? 'Sin justificación adicional.';
        $lines[] = '';

        // 3. EVALUACIÓN MULTÍMETRO POR CATEGORÍAS
        $lines[] = 'EVALUACIÓN MULTÍMETRO POR CATEGORÍAS';
        $lines[] = sprintf(
            'Score global tras topes: %d%% · Identidad: %d%% (%s) / Cumplimiento PLD: %d%% (%s) / Consistencia: %d%% (%s) / Reputacional-OSINT: %d%% (%s)',
            (int) ($exp['score_global'] ?? 0),
            (int) ($scoresCat['IDENTIDAD']['score'] ?? 0),
            $scoresCat['IDENTIDAD']['estatus'] ?? 'N/A',
            (int) ($scoresCat['PLD']['score'] ?? 0),
            $scoresCat['PLD']['estatus'] ?? 'N/A',
            (int) ($scoresCat['CONSISTENCIA']['score'] ?? 0),
            $scoresCat['CONSISTENCIA']['estatus'] ?? 'N/A',
            (int) ($scoresCat['REPUTACIONAL']['score'] ?? 0),
            $scoresCat['REPUTACIONAL']['estatus'] ?? 'N/A'
        );
        $lines[] = '';

        // 4. NIVEL DE RIESGO DE PERSONA (INSUMO EBR)
        $lines[] = 'NIVEL DE RIESGO DE PERSONA (INSUMO EBR)';
        $lines[] = '  Nivel: ' . ($ebr['nivel'] ?? 'MEDIO');
        $reglasStr = [];
        foreach ($ebr['reglas'] ?? [] as $r) {
            $reglasStr[] = sprintf('%s [fuente: %s, campo: %s]', $r['regla'] ?? '', $r['fuente'] ?? 'N/A', $r['campo'] ?? 'N/A');
        }
        $lines[] = '  Reglas que lo determinan: ' . (!empty($reglasStr) ? implode(' | ', $reglasStr) : 'Ninguna');
        $lines[] = sprintf(
            '  Debida Diligencia Reforzada recomendada: %s — %s',
            !empty($edd['recomendada']) ? 'SÍ' : 'NO',
            $edd['causa'] ?? 'Sin causa'
        );
        $lines[] = sprintf(
            '  Estatus PEP: %s · fuente: %s · fecha: %s · Asimilados a PEP: %s',
            $pep['estatus'] ?? 'PENDIENTE',
            $pep['fuente'] ?? 'Sin dato',
            $pep['fecha'] ?? 'Sin dato',
            $pep['asimilados'] ?? 'no evaluado'
        );
        $lines[] = sprintf(
            '  Fecha de consulta: %s · Reevaluar antes de: %s (intervalo: %d meses)',
            $exp['fecha_generacion'] ?? 'Sin dato',
            $exp['proxima_reevaluacion'] ?? 'Sin dato',
            (int) ($exp['intervalo_reevaluacion_meses'] ?? 6)
        );
        $lines[] = '  Conservar hasta: ' . ($exp['conservar_hasta'] ?? 'Sin dato');
        $lines[] = '';
        $lines[] = '  Factores inherentes (RCG Art. 23 Bis 2)';
        $lines[] = '  | Factor | Valor | Fuente | Estado |';
        foreach ($exp['factores_inherentes'] ?? [] as $fi) {
            $lines[] = sprintf(
                '  | %s | %s | %s | %s |',
                $fi['factor'] ?? '',
                $fi['valor'] ?? 'No provisto',
                $fi['fuente'] ?? 'Sin dato',
                $fi['estado'] ?? 'No evaluado'
            );
        }
        $lines[] = '';
        $leyendaTrans = $exp['factores_transaccionales']['leyenda_fija'] ?? config('pld.textos_fijos.factores_transaccionales');
        $declTrans = $exp['factores_transaccionales']['declarado_por_cliente'] ?? 'No provisto por el cliente';
        $lines[] = '  ' . $leyendaTrans . ' [Perfil declarado por el cliente: ' . $declTrans . ']';

        if (($persona['tipo_persona'] ?? '') === 'MORAL' && !empty($exp['beneficiario_controlador'])) {
            $bc = $exp['beneficiario_controlador'];
            $lines[] = sprintf(
                '  Beneficiario Controlador: %s — %s',
                $bc['estado'] ?? 'No evaluado',
                $bc['prelacion_aplicada'] ?? ''
            );
        }

        if (($exp['finalidad'] ?? '') === 'seleccion_personal' && !empty($exp['corroboracion_personal'])) {
            $cp = $exp['corroboracion_personal'];
            $lines[] = sprintf(
                '  Corroboración de declaración de personal: Declaración: %s | Trayectoria IMSS: %s | Honorabilidad/Sentencias: %s',
                $cp['declaracion_firmada'] ?? 'No provisto',
                $cp['corroboracion_trayectoria_imss'] ?? 'No verificable',
                $cp['delitos_patrimoniales_inhabilitacion'] ?? 'No verificable con las fuentes configuradas'
            );
        }
        $lines[] = '';

        // 5. CONTROL DE VERSIÓN (siempre presente, copiado de meta_version)
        $lines[] = 'CONTROL DE VERSIÓN';
        if (empty($meta['folio'])) {
            $lines[] = '  Versión: no asignada por el sistema';
        } else {
            $lines[] = sprintf(
                '  Folio: %s · Versión: %d · Tipo de ejecución: %s · Generado: %s',
                $meta['folio'],
                (int) ($meta['version'] ?? 1),
                $meta['tipo_ejecucion'] ?? 'INICIAL',
                $meta['fecha_generacion'] ?? 'Sin dato'
            );
            $lines[] = sprintf(
                '  Hash de esta versión: %s · Hash de la versión anterior: %s',
                $meta['hash_version'] ?? $exp['hash_version'] ?? 'Sin dato',
                $meta['hash_previo'] ?? 'no aplica'
            );
        }
        $lines[] = '';

        // 6. CAMBIOS RESPECTO A LA VERSIÓN ANTERIOR (solo en reevaluaciones — §7.10)
        if (($meta['tipo_ejecucion'] ?? 'INICIAL') !== 'INICIAL' || !empty($exp['cambios'])) {
            $lines[] = 'CAMBIOS RESPECTO A LA VERSIÓN ANTERIOR';
            $diff = $exp['cambios'] ?? [];
            if (empty($diff['cambios'])) {
                $folioPrevio = $diff['version_previa'] ?? 'versión previa';
                $lines[] = "  Sin cambios respecto a la versión {$folioPrevio} (Fecha de reevaluación: " . ($meta['fecha_generacion'] ?? '') . ")";
            } else {
                $lines[] = '  | Campo | Antes | Después | Fuente | Fecha de consulta |';
                foreach ($diff['items'] ?? [] as $item) {
                    $lines[] = sprintf(
                        '  | %s | %s | %s | %s | %s |',
                        $item['campo'] ?? '',
                        is_scalar($item['antes'] ?? null) ? (string) $item['antes'] : json_encode($item['antes'] ?? ''),
                        is_scalar($item['despues'] ?? null) ? (string) $item['despues'] : json_encode($item['despues'] ?? ''),
                        $item['fuente'] ?? 'sistema',
                        $item['fecha_consulta'] ?? ($meta['fecha_generacion'] ?? '')
                    );
                }
            }
            $lines[] = '';
        }

        // 7. HALLAZGOS CONFIRMADOS (N)
        $lines[] = 'HALLAZGOS CONFIRMADOS (' . count($hallazgos) . ')';
        if (empty($hallazgos)) {
            $lines[] = '  Sin hallazgos confirmados en las fuentes consultadas.';
        } else {
            foreach ($hallazgos as $idx => $h) {
                $num = $idx + 1;
                $lines[] = sprintf(
                    '  %d. [%s] Fuente: %s | Campo: %s | %s (%s)',
                    $num,
                    $h['categoria'] ?? '',
                    $h['fuente'] ?? '',
                    $h['campo'] ?? '',
                    $h['descripcion'] ?? '',
                    $h['efecto_score'] ?? ''
                );
            }
        }
        $lines[] = '';

        // 8. FUENTES PENDIENTES DE VERIFICACIÓN (NO CONCLUYENTES)
        $lines[] = 'FUENTES PENDIENTES DE VERIFICACIÓN (NO CONCLUYENTES)';
        if (empty($pendientes)) {
            $lines[] = '  Ninguna fuente pendiente de verificación.';
        } else {
            foreach ($pendientes as $p) {
                $lines[] = sprintf(
                    '  - Fuente: %s | HTTP: %d | Categoría: %s (Tope aplicado: %d%%) | Motivo: %s | Acción recomendada: %s',
                    $p['fuente'] ?? '',
                    (int) ($p['status_http'] ?? 0),
                    $p['categoria'] ?? '',
                    (int) ($p['tope_categoria'] ?? 89),
                    $p['motivo'] ?? '',
                    $p['accion_recomendada'] ?? ''
                );
            }
        }
        if (!empty($exp['fuentes_no_catalogadas'])) {
            foreach ($exp['fuentes_no_catalogadas'] as $nc) {
                $lines[] = sprintf(
                    '  - Fuente: %s | Estatus: %s',
                    $nc['fuente'] ?? 'DESCONOCIDA',
                    $nc['clasificacion'] ?? 'fuente no catalogada, requiere clasificación manual'
                );
            }
        }
        $lines[] = '';

        // 9. CONSENTIMIENTO LEGAL Y CUMPLIMIENTO (LFPDPPP)
        $lines[] = 'CONSENTIMIENTO LEGAL Y CUMPLIMIENTO (LFPDPPP)';
        $lines[] = sprintf(
            '  Estado de carta: %s | Fecha de firma: %s | Finalidad/base legal: %s',
            !empty($consent['otorgado']) ? 'OTORGADO' : 'NO OTORGADO',
            $consent['fecha_firma'] ?? 'No provisto',
            $consent['base_legal'] ?? 'No provisto'
        );
        $lines[] = '';

        // 10. RESUMEN DE RESULTADOS POR FUENTES
        $lines[] = 'RESUMEN DE RESULTADOS POR FUENTES';
        $lines[] = '  | Fuente | Estatus Consulta | Resultado General |';
        foreach ($exp['resumen_fuentes'] ?? [] as $rf) {
            $lines[] = sprintf(
                '  | %s | %s | %s |',
                $rf['fuente'] ?? '',
                $rf['estado_consulta'] ?? '',
                $rf['resultado_general'] ?? ''
            );
        }
        $lines[] = '';

        // 11. DETALLE POR FUENTE
        $lines[] = 'DETALLE POR FUENTE';
        foreach ($exp['resumen_fuentes'] ?? [] as $rf) {
            $crudo = $rf['resultado_crudo'] ?? [];
            // Omitir cadena MRZ cruda sin valor de lectura directa si está presente, conservando campos interpretables
            unset($crudo['mrz'], $crudo['raw_ocr']);
            if (empty($crudo)) {
                $lines[] = sprintf('  [%s]: Sin datos adicionales disponibles para esta fuente.', strtoupper($rf['fuente'] ?? ''));
            } else {
                $lines[] = sprintf('  [%s] (Fecha consulta: %s): %s', strtoupper($rf['fuente'] ?? ''), $rf['fecha_consulta'] ?? '', json_encode($crudo, JSON_UNESCAPED_UNICODE));
            }
        }
        $lines[] = '';

        // 12. DESCARGO DE RESPONSABILIDAD
        $lines[] = 'DESCARGO DE RESPONSABILIDAD';
        $lines[] = '  ' . ($exp['descargo_responsabilidad'] ?? (config('pld.textos_fijos.descargo_base') . ' ' . config('pld.textos_fijos.descargo_linea_ebr')));

        $output = implode("\n", $lines);

        // Validar que el texto formateado cumpla también la regla madre y confidencialidad Art. 63 fr. II
        PLDScoringService::assertNoForbiddenPhrases($output);

        return $output;
    }
}
