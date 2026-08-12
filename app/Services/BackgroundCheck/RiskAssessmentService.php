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

        // Ángulo de rotación horaria de la aguja desde la izquierda (0° = Izquierda/Rojo, 180° = Derecha/Verde)
        $needleAngle = round($score * 1.8, 2);
        $gaugeBase64 = $this->generateGaugePngBase64($score);

        return [
            'score'                => $score,
            'nivel_riesgo'         => $nivelRiesgo,
            'confiabilidad_label'  => $confiabilidadLabel,
            'badge_class'          => $badgeClass,
            'text_color'           => $textColor,
            'status_text'          => $statusText,
            'needle_angle'         => $needleAngle,
            'gauge_base64'         => $gaugeBase64,
            'penalties'            => $penalties,
            'total_penalties'      => count($penalties),
            'queries_evaluadas'    => $completedQueries->count(),
        ];
    }

    /**
     * Genera una imagen PNG base64 de alta resolución del velocímetro (Gauge Meter) compatible con DomPDF y navegadores.
     */
    public function generateGaugePngBase64(int $score): string
    {
        $w = 600;
        $h = 320;
        $img = imagecreatetruecolor($w, $h);

        if (function_exists('imageantialias')) {
            imageantialias($img, true);
        }

        // Fondo suave (#f8fafc)
        $bg = imagecolorallocate($img, 248, 250, 252);
        imagefill($img, 0, 0, $bg);

        // Colores de los 5 segmentos
        $cRed    = imagecolorallocate($img, 211, 47, 47);   // #d32f2f (0-20%)
        $cOrange = imagecolorallocate($img, 240, 101, 72);  // #f06548 (20-40%)
        $cYellow = imagecolorallocate($img, 247, 184, 75);  // #f7b84b (40-60%)
        $cLGreen = imagecolorallocate($img, 132, 200, 53);  // #84c835 (60-80%)
        $cGreen  = imagecolorallocate($img, 10, 179, 156);  // #0ab39c (80-100%)

        $cx = 300;
        $cy = 270;
        $rBase = 400; // Diámetro base

        $segments = [
            ['start' => 180, 'end' => 216, 'color' => $cRed],
            ['start' => 216, 'end' => 252, 'color' => $cOrange],
            ['start' => 252, 'end' => 288, 'color' => $cYellow],
            ['start' => 288, 'end' => 324, 'color' => $cLGreen],
            ['start' => 324, 'end' => 360, 'color' => $cGreen],
        ];

        // Dibujar arcos concéntricos suavizados
        imagesetthickness($img, 2);
        for ($t = -20; $t <= 20; $t++) {
            $r = $rBase + $t;
            foreach ($segments as $seg) {
                imagearc($img, $cx, $cy, $r, $r, $seg['start'], $seg['end'], $seg['color']);
            }
        }

        // Ángulo aguja en GD (180° = 0% Izquierda/Rojo -> 360° = 100% Derecha/Verde)
        $gdAngle = 180 + ($score * 1.8);
        $rad = deg2rad($gdAngle);

        $needleLen = 160;
        $nx = $cx + (int)round($needleLen * cos($rad));
        $ny = $cy + (int)round($needleLen * sin($rad));

        // Dibujar aguja oscura y pivote central
        $dark = imagecolorallocate($img, 20, 25, 35);
        imagesetthickness($img, 8);
        imageline($img, $cx, $cy, $nx, $ny, $dark);

        // Pivote exterior e interior
        imagefilledellipse($img, $cx, $cy, 28, 28, $dark);
        imagefilledellipse($img, $cx, $cy, 12, 12, $bg);

        // Buffer de salida PNG Base64
        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($data);
    }
}
