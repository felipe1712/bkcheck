<?php

namespace App\Services\BackgroundCheck;

use App\Models\Subject;
use App\Models\SourceQuery;

class RiskAssessmentService
{
    /**
     * Evalúa el nivel de riesgo e índices independientes de un sujeto basado en sus consultas.
     *
     * @param Subject $subject
     * @return array
     */
    public function calculateRisk(Subject $subject): array
    {
        $queries = SourceQuery::where('subject_id', $subject->id)->get();
        $completedQueries = $queries->where('status', 'completed');

        // ----------------------------------------------------
        // ÍNDICE 1: IDENTIDAD (Base 100%)
        // ----------------------------------------------------
        $scoreIdentidad = 100;
        $identityPenalties = [];
        $hasPrimaryIdentityIssue = false;

        // Biometría Facial INE vs Selfie (-30 pts)
        $bioQ = $completedQueries->firstWhere('source_type', 'ine_vs_selfie');
        if ($bioQ && $bioQ->estado_evaluado === 'CONFIRMADO_NEGATIVO') {
            $scoreIdentidad -= 30;
            $hasPrimaryIdentityIssue = true;
            $identityPenalties[] = [
                'fuente' => 'Biometría Facial (Rostro vs INE)',
                'puntos' => -30,
                'detalle' => 'El rostro del candidato no coincide con la credencial presentada.',
            ];
        }

        // Lista Nominal INE (-25 pts)
        $lnQ = $completedQueries->firstWhere('source_type', 'lista_nominal');
        if ($lnQ && $lnQ->estado_evaluado === 'CONFIRMADO_NEGATIVO') {
            $scoreIdentidad -= 25;
            $hasPrimaryIdentityIssue = true;
            $identityPenalties[] = [
                'fuente' => 'Padrón Electoral / Lista Nominal INE',
                'puntos' => -25,
                'detalle' => 'Credencial no localizada o no vigente en el Padrón Electoral.',
            ];
        }

        // RFC Inválido (-20 pts)
        $rfcQ = $completedQueries->firstWhere('source_type', 'rfc');
        if ($rfcQ && $rfcQ->estado_evaluado === 'CONFIRMADO_NEGATIVO') {
            $scoreIdentidad -= 20;
            $hasPrimaryIdentityIssue = true;
            $identityPenalties[] = [
                'fuente' => 'Validación de RFC ante SAT',
                'puntos' => -20,
                'detalle' => 'RFC no registrado o inactivo en los sistemas del SAT.',
            ];
        }

        $scoreIdentidad = max(0, min(100, $scoreIdentidad));
        if ($hasPrimaryIdentityIssue && $scoreIdentidad > 70) {
            $scoreIdentidad = 69;
        }

        // ----------------------------------------------------
        // ÍNDICE 2: CUMPLIMIENTO & PLD (Base 100%)
        // ----------------------------------------------------
        $scoreCumplimiento = 100;
        $compliancePenalties = [];

        // Sanciones OFAC / ONU / UE / PEPs (-45 pts)
        $sancQ = $completedQueries->firstWhere('source_type', 'sanciones');
        if ($sancQ && $sancQ->estado_evaluado === 'CONFIRMADO_NEGATIVO') {
            $scoreCumplimiento -= 45;
            $compliancePenalties[] = [
                'fuente' => 'Listas PLD / OFAC / ONU / UE / PEPs / Sanciones',
                'puntos' => -45,
                'detalle' => 'Coincidencia confirmada en listas de sanciones o boletines gubernamentales.',
            ];
        }

        // Listas SAT 69/69-B (-40 pts)
        $satQ = $completedQueries->firstWhere('source_type', 'sat_listas');
        if ($satQ && $satQ->estado_evaluado === 'CONFIRMADO_NEGATIVO') {
            $scoreCumplimiento -= 40;
            $compliancePenalties[] = [
                'fuente' => 'SAT Listas 69 / 69-B (EFOS/EDOS)',
                'puntos' => -40,
                'detalle' => 'Publicado en listado de EFOS / facturación simulada del SAT.',
            ];
        }

        // Litigios y Juicios Activos (-25 pts)
        $litQ = $completedQueries->firstWhere('source_type', 'litigios');
        if ($litQ && $litQ->estado_evaluado === 'CONFIRMADO_NEGATIVO') {
            $scoreCumplimiento -= 25;
            $compliancePenalties[] = [
                'fuente' => 'Litigios y Boletín Judicial',
                'puntos' => -25,
                'detalle' => 'Registra procesos o expedientes judiciales activos.',
            ];
        }

        $scoreCumplimiento = max(0, min(100, $scoreCumplimiento));

        // ----------------------------------------------------
        // ÍNDICE 3: CONSISTENCIA DOCUMENTAL (Base 100%)
        // ----------------------------------------------------
        $scoreConsistencia = 100;
        $consistencyPenalties = [];
        $ineFrente = $completedQueries->firstWhere('source_type', 'ine_frente')?->result?->processed_data ?? [];
        $curpData  = $completedQueries->firstWhere('source_type', 'curp')?->result?->processed_data ?? [];

        if (!empty($ineFrente['curp']) && !empty($curpData['curp'])) {
            if (strtoupper(trim($ineFrente['curp'])) !== strtoupper(trim($curpData['curp']))) {
                $scoreConsistencia -= 30;
                $consistencyPenalties[] = [
                    'fuente' => 'Cotejo Cruzado INE vs RENAPO',
                    'puntos' => -30,
                    'detalle' => 'La CURP extraída del INE no coincide con el registro oficial de RENAPO.',
                ];
            }
        }
        $scoreConsistencia = max(0, min(100, $scoreConsistencia));

        // ----------------------------------------------------
        // ÍNDICE 4: RIESGO REPUTACIONAL & OSINT (Base 100%)
        // ----------------------------------------------------
        $scoreReputacional = 100;
        $reputationalPenalties = [];
        $scoreReputacional = max(0, min(100, $scoreReputacional));

        // ----------------------------------------------------
        // SCORE GLOBAL Y RECOMENDACIÓN DE ACCIÓN
        // ----------------------------------------------------
        $allPenalties = array_merge($identityPenalties, $compliancePenalties, $consistencyPenalties, $reputationalPenalties);

        $scoreGlobal = (int)round(($scoreIdentidad * 0.35) + ($scoreCumplimiento * 0.35) + ($scoreConsistencia * 0.15) + ($scoreReputacional * 0.15));
        if ($hasPrimaryIdentityIssue && $scoreGlobal > 70) {
            $scoreGlobal = 69;
        }

        // Recomendación de Acción Ejecutiva
        if ($scoreGlobal >= 90 && !$hasPrimaryIdentityIssue) {
            $recomendacion = '🟢 Proceder sin observaciones';
            $recomendacionDetalle = 'El sujeto presenta credenciales de identidad verificadas y expediente limpio de sanciones.';
            $nivelRiesgo = 'Bajo / Mínimo';
            $badgeClass = 'bg-success text-white';
            $textColor = '#0ab39c';
            $confiabilidadLabel = 'MUY ALTA';
        } elseif ($scoreGlobal >= 70 && !$hasPrimaryIdentityIssue) {
            $recomendacion = '🟡 Validar manualmente antes de proceder';
            $recomendacionDetalle = 'Se detectaron observaciones menores o fuentes pendientes que requieren revisión por el oficial de cumplimiento.';
            $nivelRiesgo = 'Moderado';
            $badgeClass = 'bg-warning text-dark';
            $textColor = '#f7b84b';
            $confiabilidadLabel = 'MODERADA';
        } else {
            $recomendacion = '🔴 No se recomienda proceder sin autorización de nivel superior';
            $recomendacionDetalle = 'El expediente presenta alertas de alta severidad en identidad primaria o boletinación en listas de sanciones.';
            $nivelRiesgo = 'Alto / Crítico';
            $badgeClass = 'bg-danger text-white';
            $textColor = '#d32f2f';
            $confiabilidadLabel = 'ALTA INCIDENCIA';
        }

        // Fuentes pendientes de verificación (NO_CONCLUYENTE / Failed)
        $fuentesPendientes = [];
        foreach ($queries as $q) {
            if ($q->estado_evaluado === 'NO_CONCLUYENTE' || in_array($q->status, ['failed', 'error'])) {
                $fuentesPendientes[] = [
                    'fuente' => $q->source_type,
                    'nombre' => $this->getSourceLabel($q->source_type),
                    'motivo' => $q->error_message ?: 'Respuesta no concluyente de la API. Pendiente de reintento automático.',
                ];
            }
        }

        return [
            'score'                   => $scoreGlobal,
            'nivel_riesgo'            => $nivelRiesgo,
            'confiabilidad_label'     => $confiabilidadLabel,
            'badge_class'             => $badgeClass,
            'text_color'              => $textColor,
            'recomendacion'           => $recomendacion,
            'recomendacion_detalle'   => $recomendacionDetalle,
            'indices' => [
                'identidad'           => ['score' => $scoreIdentidad, 'label' => 'Identidad', 'color' => $scoreIdentidad >= 70 ? '#0ab39c' : '#d32f2f'],
                'cumplimiento'        => ['score' => $scoreCumplimiento, 'label' => 'Cumplimiento PLD', 'color' => $scoreCumplimiento >= 70 ? '#0ab39c' : '#d32f2f'],
                'consistencia'        => ['score' => $scoreConsistencia, 'label' => 'Consistencia', 'color' => $scoreConsistencia >= 70 ? '#0ab39c' : '#d32f2f'],
                'reputacional'        => ['score' => $scoreReputacional, 'label' => 'Reputacional / OSINT', 'color' => $scoreReputacional >= 70 ? '#0ab39c' : '#d32f2f'],
            ],
            'needle_angle'            => round($scoreGlobal * 1.8, 2),
            'gauge_base64'            => $this->generateGaugePngBase64($scoreGlobal),
            'penalties'               => $allPenalties,
            'total_penalties'         => count($allPenalties),
            'fuentes_pendientes'      => $fuentesPendientes,
            'queries_evaluadas'       => $completedQueries->count(),
        ];
    }

    /**
     * Obtein label for a given source_type.
     */
    protected function getSourceLabel(string $sourceType): string
    {
        return match ($sourceType) {
            'rfc' => 'Validación RFC (SAT)',
            'csd' => 'Certificados CSD (SAT)',
            'siger' => 'Registro Público (SIGER)',
            'sat_listas' => 'Listas 69/69-B (SAT)',
            'marcas' => 'Marcas Registradas (IMPI)',
            'ine_frente' => 'INE Frontal (OCR)',
            'ine_reverso' => 'INE Reverso (OCR)',
            'lista_nominal' => 'Lista Nominal INE',
            'ine_vs_selfie' => 'Biometría Facial',
            'sanciones' => 'Sanciones / OFAC / PEPs',
            'litigios' => 'Boletín Judicial / Litigios',
            'curp' => 'Validación CURP (RENAPO)',
            default => strtoupper($sourceType),
        };
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
