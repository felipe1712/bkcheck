<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector NSS / IMSS — Historial Laboral y Semanas Cotizadas.
 *
 * Consulta el historial laboral del sujeto ante el IMSS a través de la API
 * de NuFi: semanas cotizadas, últimos patrones, continuidad de cotización.
 *
 * Aplica únicamente a personas físicas con NSS (Número de Seguridad Social)
 * registrado. Requiere CURP o NSS como identificador.
 *
 * Endpoint NuFi esperado: POST /nss/consulta o equivalente.
 */
class NufiNssConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'nss_imss';
    }

    public function getName(): string
    {
        return 'Semanas Cotizadas IMSS / NSS';
    }

    public function getMinTierLevel(): int
    {
        return 2;
    }

    public function appliesTo(Subject $subject): bool
    {
        return $subject->tipo === 'persona_fisica'
            && (!empty($subject->nss) || !empty($subject->curp));
    }

    protected function callApi(Subject $subject): array
    {
        $payload = [];

        if (!empty($subject->nss)) {
            $payload['nss'] = $subject->nss;
        }
        if (!empty($subject->curp)) {
            $payload['curp'] = strtoupper($subject->curp);
        }

        $response = $this->postRequest('/nss/consulta', $payload);
        return $this->normalizar($response);
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'nss'                     => $subject->nss ?? '12345678901',
            'curp'                    => strtoupper($subject->curp ?? 'XXXX000000XXXXXX00'),
            'nombre'                  => $subject->name_or_company,
            'semanas_cotizadas'       => 312,
            'semanas_cotizadas_infonavit' => 288,
            'ultimo_patron'           => 'EMPRESA DEMO SA DE CV',
            'rfc_ultimo_patron'       => 'EDM120101XYZ',
            'fecha_ultima_cotizacion' => now()->subMonths(2)->format('Y-m-d'),
            'activo_actualmente'      => false,
            'total_patrones'          => 4,
            'salario_base_cotizacion' => 850.00,
            'historial_empleos'       => [
                [
                    'patron'           => 'EMPRESA DEMO SA DE CV',
                    'rfc_patron'       => 'EDM120101XYZ',
                    'fecha_inicio'     => '2018-03-01',
                    'fecha_baja'       => now()->subMonths(2)->format('Y-m-d'),
                    'semanas'          => 210,
                    'tipo_movimiento'  => 'Baja voluntaria',
                ],
                [
                    'patron'           => 'COMERCIALIZADORA ALFA SRL',
                    'rfc_patron'       => 'CAL080505ABC',
                    'fecha_inicio'     => '2014-01-15',
                    'fecha_baja'       => '2018-02-28',
                    'semanas'          => 102,
                    'tipo_movimiento'  => 'Baja por despido',
                ],
            ],
            'mensaje' => '[MOCK] Consulta de historial IMSS/NSS completada exitosamente.',
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return [
            'nss'  => $subject->nss  ?? null,
            'curp' => strtoupper($subject->curp ?? ''),
        ];
    }

    private function normalizar(array $response): array
    {
        return [
            'nss'                        => $response['nss']                     ?? null,
            'curp'                       => $response['curp']                    ?? null,
            'nombre'                     => $response['nombre']                  ?? null,
            'semanas_cotizadas'          => $response['semanas_cotizadas']       ?? null,
            'semanas_cotizadas_infonavit'=> $response['semanas_cotizadas_infonavit'] ?? null,
            'ultimo_patron'              => $response['ultimo_patron']            ?? null,
            'rfc_ultimo_patron'          => $response['rfc_ultimo_patron']        ?? null,
            'fecha_ultima_cotizacion'    => $response['fecha_ultima_cotizacion']  ?? null,
            'activo_actualmente'         => $response['activo_actualmente']       ?? null,
            'total_patrones'             => $response['total_patrones']           ?? null,
            'salario_base_cotizacion'    => $response['salario_base_cotizacion']  ?? null,
            'historial_empleos'          => $response['historial_empleos']        ?? [],
            'mensaje'                    => $response['mensaje']                  ?? null,
        ];
    }
}
