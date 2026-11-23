<?php

namespace App\Services\BackgroundCheck;

use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * Consolida todos los hallazgos de las fuentes ejecutadas en una
 * "Radiografía de Localización" orientada a despachos jurídicos e investigadores.
 */
class LocationProfileService
{
    public function build(Subject $subject, Collection $queries): array
    {
        $byType = $queries->where('status', 'completed')->keyBy('source_type');

        $curpData   = $byType->get('curp')?->result?->processed_data ?? [];
        $rfcData    = $byType->get('rfc')?->result?->processed_data ?? [];
        $buroData   = $byType->get('score_crediticio')?->result?->processed_data ?? [];
        $domData    = $byType->get('comprobante_domicilio')?->result?->processed_data ?? [];
        $denueData  = $byType->get('denue')?->result?->processed_data ?? [];
        $nssData    = $byType->get('nss_imss')?->result?->processed_data ?? [];
        $cedulaData = $byType->get('cedula_profesional')?->result?->processed_data ?? [];
        $sigerData  = $byType->get('siger')?->result?->processed_data ?? [];
        $penalData = [
            'tiene_antecedentes' => !empty($litigData['tiene_antecedentes_penales']),
            'total_registros'    => (int) ($litigData['total_penales'] ?? 0),
            'registros'          => $litigData['juicios_penales'] ?? [],
        ];
        $litigData  = $byType->get('litigios')?->result?->processed_data ?? [];
        $osintData  = $byType->get('presencia_en_linea')?->result?->processed_data ?? [];
        $horusData  = $byType->get('horus_osint')?->result?->processed_data ?? [];
        $robinData  = $byType->get('robin_darkweb')?->result?->processed_data ?? [];
        $tlalocPhone= $byType->get('tlaloc_phone')?->result?->processed_data ?? [];

        // 1. Domicilios consolidados
        $domicilios = [];
        if (!empty($subject->address)) {
            $domicilios[] = [
                'direccion'     => $subject->address,
                'fuente'        => 'Registro Inicial',
                'tipo'          => 'Declarado',
                'fecha_reporte' => $subject->created_at?->format('Y-m-d'),
            ];
        }
        foreach (($buroData['domicilios_conocidos'] ?? []) as $d) {
            if (!empty($d['direccion'])) {
                $domicilios[] = [
                    'direccion'     => $d['direccion'],
                    'fuente'        => $d['fuente'] ?? 'Buró de Crédito',
                    'tipo'          => $d['tipo'] ?? 'Reportado',
                    'fecha_reporte' => $d['fecha_reporte'] ?? null,
                ];
            }
        }
        if (!empty($domData['domicilio_extraido'])) {
            $domicilios[] = [
                'direccion'     => $domData['domicilio_extraido'],
                'fuente'        => 'Comprobante de Domicilio (OCR)',
                'tipo'          => 'Comprobado',
                'fecha_reporte' => $domData['fecha_documento'] ?? null,
            ];
        }
        foreach (($denueData['unidades_economicas'] ?? []) as $u) {
            if (!empty($u['direccion'])) {
                $domicilios[] = [
                    'direccion'     => $u['direccion'] . ' (' . ($u['nombre'] ?? 'Establecimiento') . ')',
                    'fuente'        => 'INEGI DENUE',
                    'tipo'          => 'Actividad Comercial',
                    'fecha_reporte' => null,
                ];
            }
        }

        // 2. Actividad laboral y empresarial
        $empleos = $nssData['empleos'] ?? [];
        $patronActual = !empty($empleos) ? $empleos[0] : null;

        // 3. Huella digital / OSINT
        $redes = array_merge(
            $osintData['plataformas_encontradas'] ?? [],
            $osintData['perfiles_correlacionados'] ?? []
        );

        return [
            'identidad' => [
                'nombre_completo'    => $subject->name_or_company,
                'curp'               => $subject->curp,
                'curp_valida'        => $curpData['valida'] ?? null,
                'rfc'                => $subject->rfc,
                'rfc_source'         => $subject->rfc_source ?? 'manual',
                'rfc_valido'         => $rfcData['valido'] ?? null,
                'confianza_score'    => $subject->identity_confidence_score,
                'fecha_nacimiento'   => $subject->birth_date?->format('Y-m-d') ?? ($curpData['fecha_nacimiento'] ?? null),
                'estado_nacimiento'  => $curpData['estado_nacimiento'] ?? null,
            ],
            'domicilios' => $domicilios,
            'telefonos'  => array_values(array_unique(array_filter(array_merge(
                [$subject->phone],
                array_column($buroData['telefonos_reportados'] ?? [], 'numero')
            )))),
            'actividad_laboral' => [
                'nss'                 => $nssData['nss'] ?? $subject->nss,
                'activo_imss'         => $nssData['activo_actualmente'] ?? null,
                'semanas_cotizadas'   => $nssData['semanas_cotizadas'] ?? null,
                'patron_actual'        => $patronActual,
                'historial_empleos'    => $empleos,
                'cedulas_profesionales'=> $cedulaData['cedulas'] ?? [],
                'vinculos_siger'       => $sigerData['actos'] ?? $sigerData['sociedades'] ?? [],
            ],
            'riesgo_legal' => [
                'antecedentes_penales' => $penalData,
                'litigios'             => $litigData,
            ],
            'huella_digital' => [
                'redes_encontradas' => $redes,
                'total_redes'       => count($redes),
                'horus'             => $horusData,
                'tlaloc_phone'      => $tlalocPhone,
                'robin'             => $robinData,
            ],
        ];
    }
}
