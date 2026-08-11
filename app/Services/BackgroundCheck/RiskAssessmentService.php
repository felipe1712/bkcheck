<?php

namespace App\Services\BackgroundCheck;

use App\Models\Subject;
use App\Models\SourceQuery;

class RiskAssessmentService
{
    /**
     * Evalúa el nivel de riesgo e índice de confiabilidad de un sujeto basado en sus consultas.
     *
     * @param Subject $subject
     * @return array
     */
    public function calculateRisk(Subject $subject): array
    {
        $queries = SourceQuery::where('subject_id', $subject->id)->get();
        $completedQueries = $queries->where('status', 'completed');

        $score = 100; // Puntaje base (100% Confiable / Riesgo Mínimo)
        $penalties = [];

        // 1. Sanciones Internacionales / OFAC / PEPs (-40 pts)
        $sancionesQuery = $completedQueries->firstWhere('source_type', 'sanciones');
        if ($sancionesQuery) {
            $sancData = $sancionesQuery->result?->processed_data ?? [];
            $rawHits = $sancData['hits'] ?? [];
            $validHits = array_filter($rawHits, function($h) {
                $name = trim($h['nombre_encontrado'] ?? '');
                return !empty($name) && strtoupper($name) !== 'N/A';
            });
            if (!empty($sancData['encontrado']) && count($validHits) > 0) {
                $score -= 40;
                $penalties[] = [
                    'fuente' => 'Listas Negras / OFAC / PEPs',
                    'puntos' => -40,
                    'detalle' => 'Coincidencia localizada en listas de sanciones o vigilancia internacional.',
                ];
            }
        }

        // 2. Lista SAT 69/69-B (-35 pts)
        $satQuery = $completedQueries->firstWhere('source_type', 'sat_listas');
        if ($satQuery) {
            $satData = $satQuery->result?->processed_data ?? [];
            if (!empty($satData['en_lista_69b'])) {
                $score -= 35;
                $penalties[] = [
                    'fuente' => 'SAT Listas 69 / 69-B',
                    'puntos' => -35,
                    'detalle' => 'Publicado en listado de EFOS o presuntos/definitivos del SAT.',
                ];
            }
        }

        // 3. Litigios y Juicios Activos (-25 pts)
        $litigiosQuery = $completedQueries->firstWhere('source_type', 'litigios');
        if ($litigiosQuery) {
            $litData = $litigiosQuery->result?->processed_data ?? [];
            if (!empty($litData['tiene_juicios'])) {
                $score -= 25;
                $penalties[] = [
                    'fuente' => 'Litigios y Boletín Judicial',
                    'puntos' => -25,
                    'detalle' => 'Registra procesos o expedientes judiciales activos.',
                ];
            }
        }

        // 4. Biometría Facial (INE vs Selfie) (-20 pts si no coincide)
        $bioQuery = $completedQueries->firstWhere('source_type', 'ine_vs_selfie');
        if ($bioQuery) {
            $bioData = $bioQuery->result?->processed_data ?? [];
            if (isset($bioData['coincide_rostro']) && !$bioData['coincide_rostro']) {
                $score -= 20;
                $penalties[] = [
                    'fuente' => 'Comparación Biométrica Facial',
                    'puntos' => -20,
                    'detalle' => 'El rostro del candidato no coincide con la fotografía del INE.',
                ];
            }
        }

        // 5. Lista Nominal del INE (-15 pts si no está vigente o no fue localizada)
        $lnQuery = $completedQueries->firstWhere('source_type', 'lista_nominal');
        if ($lnQuery) {
            $lnData = $lnQuery->result?->processed_data ?? [];
            if (isset($lnData['valida']) && !$lnData['valida']) {
                $score -= 15;
                $penalties[] = [
                    'fuente' => 'Lista Nominal del INE',
                    'puntos' => -15,
                    'detalle' => 'Credencial no localizada o no vigente en el Padrón Electoral.',
                ];
            }
        }

        // 6. Certificado CSD / SAT (-10 pts si está cancelado/inactivo)
        $csdQuery = $completedQueries->firstWhere('source_type', 'csd');
        if ($csdQuery) {
            $csdData = $csdQuery->result?->processed_data ?? [];
            if (isset($csdData['valido']) && !$csdData['valido']) {
                $score -= 10;
                $penalties[] = [
                    'fuente' => 'Certificado Sellos Digitales (CSD)',
                    'puntos' => -10,
                    'detalle' => 'Certificado de sello digital no válido o revocado por el SAT.',
                ];
            }
        }

        // Limitar puntaje entre 0 y 100
        $score = max(0, min(100, $score));

        // Determinar Nivel de Riesgo y Colores
        if ($score >= 90) {
            $nivelRiesgo = 'Bajo / Mínimo';
            $confiabilidadLabel = 'MUY ALTA';
            $badgeClass = 'bg-success text-white';
            $textColor = '#0ab39c'; // Teal / Green
            $statusText = 'Expediente Limpio (Confiable)';
        } elseif ($score >= 75) {
            $nivelRiesgo = 'Bajo';
            $confiabilidadLabel = 'ALTA';
            $badgeClass = 'bg-info text-white';
            $textColor = '#299cdb'; // Light Blue
            $statusText = 'Riesgo Controlado';
        } elseif ($score >= 50) {
            $nivelRiesgo = 'Medio / Moderado';
            $confiabilidadLabel = 'MODERADA';
            $badgeClass = 'bg-warning text-dark';
            $textColor = '#f7b84b'; // Yellow / Orange
            $statusText = 'Atención Requerida';
        } elseif ($score >= 25) {
            $nivelRiesgo = 'Alto';
            $confiabilidadLabel = 'BAJA';
            $badgeClass = 'bg-warning-subtle text-danger border border-danger';
            $textColor = '#f06548'; // Dark Orange / Red
            $statusText = 'Alerta de Cumplimiento';
        } else {
            $nivelRiesgo = 'Crítico / No Confiable';
            $confiabilidadLabel = 'NULA';
            $badgeClass = 'bg-danger text-white';
            $textColor = '#d32f2f'; // Deep Red
            $statusText = 'Alto Riesgo Detectado';
        }

        // Ángulo de la aguja en el Gauge semicircular (180° = Izquierda/Rojo, 0° = Derecha/Verde)
        $needleAngle = round(180 - ($score * 1.8), 2);

        return [
            'score'                => $score,
            'nivel_riesgo'         => $nivelRiesgo,
            'confiabilidad_label'  => $confiabilidadLabel,
            'badge_class'          => $badgeClass,
            'text_color'           => $textColor,
            'status_text'          => $statusText,
            'needle_angle'         => $needleAngle,
            'penalties'            => $penalties,
            'total_penalties'      => count($penalties),
            'queries_evaluadas'    => $completedQueries->count(),
        ];
    }
}
