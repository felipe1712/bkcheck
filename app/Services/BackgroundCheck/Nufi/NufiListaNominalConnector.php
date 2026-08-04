<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;
use App\Models\SourceQuery;

class NufiListaNominalConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'lista_nominal';
    }

    public function getName(): string
    {
        return 'Validación de Credencial INE / Lista Nominal (Padrón Electoral)';
    }

    public function getMinTierLevel(): int
    {
        return 2;
    }

    public function appliesTo(Subject $subject): bool
    {
        return $subject->tipo === 'persona_fisica';
    }

    protected function callApi(Subject $subject): array
    {
        // Extract extracted OCR data from ine_frente and ine_reverso queries if present
        $frenteQuery = SourceQuery::withoutGlobalScopes()
            ->where('subject_id', $subject->id)
            ->where('source_type', 'ine_frente')
            ->where('status', 'completed')
            ->latest()
            ->first();

        $reversoQuery = SourceQuery::withoutGlobalScopes()
            ->where('subject_id', $subject->id)
            ->where('source_type', 'ine_reverso')
            ->where('status', 'completed')
            ->latest()
            ->first();

        $fData = $frenteQuery?->result?->processed_data ?? [];
        $rData = $reversoQuery?->result?->processed_data ?? [];

        $model = $rData['model'] ?? $fData['model'] ?? '';
        $tipo = 'G';
        if (preg_match('/Tipo([A-H])/i', $model, $m)) {
            $tipo = strtoupper($m[1]);
        } elseif (!empty($fData['tipo']) && in_array(strtoupper($fData['tipo']), ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'])) {
            $tipo = strtoupper($fData['tipo']);
        }

        $cic = (string)($rData['cic'] ?? $rData['numero_identificador'] ?? $fData['cic'] ?? '');
        $cic = preg_replace('/\D/', '', $cic);

        $claveElector = (string)($fData['clave_elector'] ?? $fData['clave'] ?? '');
        $claveElector = strtoupper(trim($claveElector));

        $emision = (string)($fData['numero_emision'] ?? $fData['emision'] ?? '01');
        $emision = preg_replace('/\D/', '', $emision);
        if (strlen($emision) > 2) {
            $emision = '01';
        } else {
            $emision = str_pad($emision ?: '01', 2, '0', STR_PAD_LEFT);
        }

        $ocr = (string)($rData['codigo_ocr'] ?? $rData['ocr'] ?? $fData['ocr'] ?? '');
        $identificador = (string)($rData['identificador_del_ciudadano'] ?? $cic);

        $body = [
            'tipo_identificacion'        => $tipo,
            'cic'                        => $cic,
            'identificador_del_ciudadano' => $identificador,
            'ocr'                        => $ocr,
            'clave_de_elector'           => $claveElector,
            'numero_de_emision'          => $emision,
        ];

        try {
            $response = $this->postRequest('/lista_nominal/validar/v2', $body);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), '400') || str_contains($e->getMessage(), 'No se obtuvieron datos')) {
                return [
                    'valida'      => false,
                    'activa'      => false,
                    'estado'      => 'No encontrada o no vigente en el Padrón Electoral / Lista Nominal del INE.',
                    'comentarios' => ['Los datos de la credencial no coinciden con un registro activo en el INE.'],
                    'information' => 'Respuesta API INE: Parámetros no coincidentes o credencial no vigente.',
                    'mensaje'     => 'No se obtuvieron datos de la consulta con los parámetros seleccionados.',
                    'raw_status'  => 'Error',
                    'detalles'    => [],
                ];
            }
            throw $e;
        }

        $rawBody = $response['body'] ?? $response;
        $statusStr = $rawBody['status'] ?? 'Success';
        $messageStr = $rawBody['message'] ?? 'Operación exitosa.';
        $dataList = $rawBody['data'] ?? [];

        $firstItem = is_array($dataList) && count($dataList) > 0 ? $dataList[0] : null;

        $activa = (bool)($firstItem['activa'] ?? false);
        $estado = $firstItem['estado'] ?? ($statusStr === 'Error' ? $messageStr : 'No encontrada en Padrón');
        $comentarios = $firstItem['comentarios'] ?? ($messageStr ? [$messageStr] : []);
        $info = $firstItem['information'] ?? '';

        return [
            'valida'      => $activa,
            'activa'      => $activa,
            'estado'      => $estado,
            'comentarios' => $comentarios,
            'information' => $info,
            'mensaje'     => $messageStr,
            'raw_status'  => $statusStr,
            'detalles'    => $firstItem ?? [],
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'valida'      => true,
            'activa'      => true,
            'estado'      => 'La credencial esta vigente',
            'comentarios' => [
                'Tus datos se encuentran en el Padrón Electoral.'
            ],
            'information' => "CIC\t183657717\r\nClave de elector\tHGLPLS92090914H200\r\nTus datos se encuentran en el Padrón Electoral.",
            'mensaje'     => 'Operación exitosa.',
            'raw_status'  => 'Success',
            'detalles'    => [
                'information' => "CIC\t183657717\r\nClave de elector\tHGLPLS92090914H200\r\nTus datos se encuentran en el Padrón Electoral.",
                'activa'      => true,
                'estado'      => 'La credencial esta vigente',
                'comentarios' => ['Tus datos se encuentran en el Padrón Electoral.']
            ]
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return [
            'tipo_identificacion'        => 'G',
            'cic'                        => '183657717',
            'identificador_del_ciudadano' => '116375842',
            'ocr'                        => '1023123491540',
            'clave_de_elector'           => 'HGLPLS92090914H200',
            'numero_de_emision'          => '00',
        ];
    }
}
