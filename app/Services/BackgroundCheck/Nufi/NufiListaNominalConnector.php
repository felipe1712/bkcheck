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

    /**
     * Construye el payload estricto por tipo_identificacion según la especificación oficial de NuFi
     * (/lista_nominal/validar/v2):
     * - Tipo C: tipo_identificacion, clave_de_elector, numero_de_emision, ocr
     * - Tipo D: tipo_identificacion, cic, ocr
     * - Tipo E, F, G, H: tipo_identificacion, cic (9 dígitos), identificador_del_ciudadano (9 dígitos)
     */
    protected function buildListaNominalPayload(Subject $subject): array
    {
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

        $model = (string) ($rData['model'] ?? $fData['model'] ?? '');
        $tipo = 'G';
        if (preg_match('/Tipo\s*([A-J])/i', $model, $m)) {
            $tipo = strtoupper($m[1]);
        } elseif (!empty($fData['tipo']) && in_array(strtoupper(trim((string) $fData['tipo'])), ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'])) {
            $tipo = strtoupper(trim((string) $fData['tipo']));
        }

        $mrzRaw = (string) ($rData['mrz'] ?? $rData['codigo_ocr'] ?? $rData['ocr'] ?? $fData['ocr'] ?? '');
        $cic = (string) ($rData['cic'] ?? $rData['numero_identificador'] ?? $fData['cic'] ?? '');
        $cic = preg_replace('/\D/', '', $cic);

        $ocrNumeric = (string) ($rData['ocr'] ?? $rData['codigo_ocr'] ?? $fData['ocr'] ?? '');
        $identificador = (string) ($rData['identificador_del_ciudadano'] ?? '');
        $emisionMrz = null;

        // Si tenemos cadena MRZ completa (ej. IDMEX1927756324<<4314002317638 7804012H2912316MEX<02<<39774<6 ...)
        if (preg_match('/IDMEX(\d{9})\d?<+(\d{12,13})/i', $mrzRaw, $m)) {
            // Los primeros 9 dígitos tras IDMEX son el CIC; el 10° dígito es el verificador
            if (empty($cic) || strlen($cic) === 10) {
                $cic = $m[1];
            }
            // Los 13 dígitos tras << son el OCR; los últimos 9 dígitos son el identificador_del_ciudadano
            $ocrNumeric = $m[2];
            $identificador = substr($m[2], -9);
        } else {
            if (strlen($cic) === 10 && str_contains($mrzRaw, 'IDMEX' . $cic)) {
                $cic = substr($cic, 0, 9);
            }
            // Si ocrNumeric contiene letras (como IDMEX...), limpiar o extraer bloque de 12-13 dígitos
            if (preg_match('/<<(\d{12,13})/', $ocrNumeric, $mOcr)) {
                $ocrNumeric = $mOcr[1];
            } else {
                $ocrNumeric = preg_replace('/\D/', '', $ocrNumeric);
            }

            if (empty($identificador) || $identificador === $cic || strlen(preg_replace('/\D/', '', $identificador)) === 10) {
                if (strlen($ocrNumeric) >= 9) {
                    $identificador = substr($ocrNumeric, -9);
                } else {
                    $identificador = $cic;
                }
            }
        }

        if (preg_match('/MEX<+(\d{2})/i', $mrzRaw, $mEm)) {
            $emisionMrz = $mEm[1];
        }

        $claveElector = strtoupper(trim((string) ($fData['clave_elector'] ?? $fData['clave'] ?? '')));

        $emision = preg_replace('/\D/', '', (string) ($fData['numero_emision'] ?? $fData['emision'] ?? ''));
        if (strlen($emision) > 2 || empty($emision)) {
            $emision = $emisionMrz ?: '01';
        } else {
            $emision = str_pad($emision, 2, '0', STR_PAD_LEFT);
        }

        // Enviar únicamente las variables correspondientes al tipo de identificación
        if ($tipo === 'C') {
            return [
                'tipo_identificacion' => 'C',
                'clave_de_elector'    => $claveElector,
                'numero_de_emision'   => $emision,
                'ocr'                 => $ocrNumeric,
            ];
        }

        if ($tipo === 'D') {
            return [
                'tipo_identificacion' => 'D',
                'cic'                 => $cic,
                'ocr'                 => $ocrNumeric,
            ];
        }

        // Tipos E, F, G, H (y posteriores): requieren únicamente tipo_identificacion, cic e identificador_del_ciudadano
        return [
            'tipo_identificacion'        => $tipo,
            'cic'                        => $cic,
            'identificador_del_ciudadano' => $identificador,
        ];
    }

    protected function callApi(Subject $subject): array
    {
        $body = $this->buildListaNominalPayload($subject);

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
        ];
    }
}
