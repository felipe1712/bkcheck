<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector NSS / IMSS — Historial Laboral y Semanas Cotizadas.
 *
 * Endpoints Oficiales NuFi:
 * 1. Alta: POST /numero_seguridad_social/v2
 *    Body: { "webhook": "", "curp": "AAAA010101HDFAAA01", "ultimo_empleo": true }
 * 2. Estatus: POST /numero_seguridad_social/v2/status
 *    Body: { "uuid_historial": "00000000-0000-4000-8000-000000000001" }
 */
class NufiNssConnector extends NufiConnector
{
    protected string $apiKeyCategory = 'enrichment';

    public function getIdentifier(): string
    {
        return 'nss_imss';
    }

    public function getName(): string
    {
        return 'Historial Laboral IMSS / Semanas Cotizadas';
    }

    public function getMinTierLevel(): int
    {
        return 2;
    }

    public function appliesTo(Subject $subject): bool
    {
        return $subject->tipo === 'persona_fisica'
            && (!empty($subject->curp) || !empty($subject->nss));
    }

    protected function callApi(Subject $subject): array
    {
        $curp = strtoupper(trim($subject->curp ?? ''));

        // 1. Solicitud de Alta de Consulta NSS / Historial Laboral
        $altaPayload = [
            'webhook'        => config('background_check.nufi.webhook_url', ''),
            'curp'           => $curp,
            'ultimo_empleo'  => true,
        ];

        $altaResponse = $this->postRequest('/numero_seguridad_social/v2', $altaPayload);
        $logAlta = $this->lastLog;

        $uuid = $altaResponse['data']['uuid'] ?? $altaResponse['uuid'] ?? '';

        if (empty($uuid)) {
            $uuid = '00000000-0000-4000-8000-' . sprintf('%012d', $subject->id);
        }

        // 2. Consulta de Estatus / Resultado por UUID
        $statusPayload = [
            'uuid_historial' => $uuid,
        ];

        $statusResponse = $this->postRequest('/numero_seguridad_social/v2/status', $statusPayload);
        $logStatus = $this->lastLog;

        // Consolidar bitácora de auditoría
        $this->lastLog = [
            'url'     => rtrim($this->baseUrl, '/') . '/numero_seguridad_social/v2 (Consolidado Alta y Status)',
            'method'  => 'POST',
            'headers' => $logStatus['headers'] ?? $logAlta['headers'] ?? [],
            'body'    => [
                'alta_payload'   => $altaPayload,
                'status_payload' => $statusPayload,
            ],
            'response' => [
                'status' => $logStatus['response']['status'] ?? $logAlta['response']['status'] ?? 200,
                'body'   => [
                    'alta_response'   => $altaResponse,
                    'status_response' => $statusResponse,
                ],
            ],
        ];

        return $this->normalizar($statusResponse, $uuid, $subject);
    }

    protected function mockResponse(Subject $subject): array
    {
        $uuidMock = 'MOCK-UUID-' . strtoupper(substr(md5($subject->id), 0, 8));

        return [
            'uuid_historial'      => $uuidMock,
            'nss'                 => $subject->nss ?? '12345678901',
            'curp'                => strtoupper($subject->curp ?? 'AAAA010101HDFAAA01'),
            'nombre'              => strtoupper($subject->name_or_company),
            'semanas_cotizadas'   => 312,
            'semanas_descontadas' => 0,
            'semanas_reintegradas'=> 0,
            'fecha_emision'       => now()->format('d/m/Y'),
            'activo_actualmente'  => true,
            'empleos'             => [
                [
                    'patron'            => 'EMPRESA DE EJEMPLO SA DE CV',
                    'registro_patronal' => 'E1234567',
                    'entidad_federativa'=> 'CIUDAD DE MEXICO',
                    'fecha_alta'        => now()->subYears(3)->format('Y-m-d'),
                    'fecha_baja'        => 'vigente',
                    'salario_base'      => '$1,250.00',
                ],
                [
                    'patron'            => 'COMERCIALIZADORA ALFA SRL',
                    'registro_patronal' => 'A9876543',
                    'entidad_federativa'=> 'JALISCO',
                    'fecha_alta'        => now()->subYears(6)->format('Y-m-d'),
                    'fecha_baja'        => now()->subYears(3)->format('Y-m-d'),
                    'salario_base'      => '$850.00',
                ],
            ],
            'status'              => 'termino',
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return [
            'curp'          => strtoupper($subject->curp ?? ''),
            'ultimo_empleo' => true,
        ];
    }

    private function normalizar(array $response, string $uuid, Subject $subject): array
    {
        $historial  = $response['data']['historial'] ?? [];
        $info       = $historial['info'] ?? [];
        $ocr        = $info['ocr'] ?? [];
        $datos      = $ocr['datos'] ?? [];
        $rawEmpleos = $ocr['empleos'] ?? [];

        $empleos = [];
        if (is_array($rawEmpleos)) {
            foreach ($rawEmpleos as $e) {
                if (is_array($e)) {
                    $empleos[] = [
                        'patron'            => $e['patron'] ?? 'N/A',
                        'registro_patronal' => $e['registro_patronal'] ?? 'N/A',
                        'entidad_federativa'=> $e['entidda_federativa'] ?? $e['entidad_federativa'] ?? 'N/A',
                        'fecha_alta'        => $e['fecha_alta'] ?? 'N/A',
                        'fecha_baja'        => $e['fecha_baja'] ?? 'Vigente',
                        'salario_base'      => $e['salario_base'] ?? 'N/A',
                    ];
                }
            }
        }

        $activo = count($empleos) > 0 && strtolower($empleos[0]['fecha_baja']) === 'vigente';

        return [
            'uuid_historial'      => $uuid,
            'nss'                 => $datos['nss'] ?? $info['numero_seguridad_social'] ?? $subject->nss ?? 'N/A',
            'curp'                => $datos['curp'] ?? $info['curp'] ?? $subject->curp ?? 'N/A',
            'nombre'              => $datos['nombre'] ?? $subject->name_or_company,
            'semanas_cotizadas'   => $datos['semanas_cotizadas'] ?? null,
            'semanas_descontadas' => $datos['semanas_descontadas'] ?? null,
            'semanas_reintegradas'=> $datos['semanas_reintegradas'] ?? null,
            'fecha_emision'       => $datos['fecha_emision'] ?? null,
            'activo_actualmente'  => $activo,
            'empleos'             => $empleos,
            'status'              => $historial['status']['mensaje'] ?? 'termino',
        ];
    }
}
