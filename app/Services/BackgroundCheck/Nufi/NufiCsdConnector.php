<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Certificados del SAT (CSD / FIEL / Sellos Digitales).
 *
 * Endpoint Oficial NuFi: POST /certificadosat/v1/consultar/consultar
 * Header: NUFI-API-KEY
 * Payload: { "rfc": "NWM9709244W4" }
 */
class NufiCsdConnector extends NufiConnector
{
    protected string $apiKeyCategory = 'enrichment';

    public function getIdentifier(): string
    {
        return 'csd';
    }

    public function getName(): string
    {
        return 'Certificados CSD y e-Firma (SAT)';
    }

    public function getMinTierLevel(): int
    {
        return 3;
    }

    public function appliesTo(Subject $subject): bool
    {
        return !empty($subject->rfc);
    }

    protected function callApi(Subject $subject): array
    {
        $response = $this->postRequest('/certificadosat/v1/consultar/consultar', [
            'rfc' => strtoupper(trim($subject->rfc)),
        ]);

        $data = $response['data'] ?? [];
        $rawCerts = $data['certificados'] ?? [];

        $certificados = [];
        foreach ($rawCerts as $c) {
            $certificados[] = [
                'numero_serie' => $c['numero_serie'] ?? null,
                'estado'       => $c['estado'] ?? 'Desconocido',
                'tipo'         => $c['tipo'] ?? 'SELLO',
                'fecha_inicio' => $c['fecha_inicial'] ?? $c['fecha_inicio'] ?? null,
                'fecha_fin'    => $c['fecha_final'] ?? $c['fecha_fin'] ?? null,
                'certificado'  => $c['certificado'] ?? null,
            ];
        }

        return [
            'status'       => $response['status'] ?? 'success',
            'rfc'          => $data['rfc'] ?? $subject->rfc,
            'razon_social' => $data['razon_social'] ?? null,
            'certificados' => $certificados,
            'total'        => count($certificados),
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'status'       => 'success',
            'rfc'          => strtoupper($subject->rfc ?? 'NWM9709244W4'),
            'razon_social' => $subject->name_or_company ?? 'EMPRESA / PERSONA FICTICIA SA DE CV',
            'total'        => 2,
            'certificados' => [
                [
                    'numero_serie' => '00001000000514203894',
                    'estado'       => 'Activo',
                    'tipo'         => 'SELLO',
                    'fecha_inicio' => '2022-07-28 16:55:12',
                    'fecha_fin'    => '2026-07-28 16:55:12',
                ],
                [
                    'numero_serie' => '00001000000507729458',
                    'estado'       => 'Activo',
                    'tipo'         => 'FIEL',
                    'fecha_inicio' => '2021-06-10 16:04:17',
                    'fecha_fin'    => '2025-06-10 16:04:57',
                ]
            ]
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return [
            'rfc' => $subject->rfc,
        ];
    }
}
