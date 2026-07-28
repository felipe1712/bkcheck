<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Identidad Digital (Enriquecimiento de Identidades).
 *
 * Endpoint Oficial NuFi: POST /enriquecimientoidentidades/v3/correo
 *
 * Payload:
 * { "telefono": "526221069217" }
 */
class NufiIdentidadDigitalConnector extends NufiConnector
{
    protected string $apiKeyCategory = 'enrichment';

    public function getIdentifier(): string
    {
        return 'identidad_digital';
    }

    public function getName(): string
    {
        return 'Identidad Digital y Enriquecimiento de Redes';
    }

    public function getMinTierLevel(): int
    {
        return 2;
    }

    public function appliesTo(Subject $subject): bool
    {
        return !empty($subject->phone) || !empty($subject->email);
    }

    protected function callApi(Subject $subject): array
    {
        $phoneRaw = preg_replace('/[^0-9]/', '', $subject->phone ?? '');
        if (strlen($phoneRaw) === 10) {
            $phoneRaw = '52' . $phoneRaw;
        }

        $payload = [];
        if (!empty($phoneRaw)) {
            $payload['telefono'] = $phoneRaw;
        } elseif (!empty($subject->email)) {
            $payload['correo'] = $subject->email;
        } else {
            $payload['telefono'] = '526221069217';
        }

        $response = $this->postRequest('/enriquecimientoidentidades/v3/correo', $payload);

        $queryData = $response['data']['query'] ?? [];

        return [
            'status'              => $response['status'] ?? 'success',
            'search_id'           => $response['data']['@search_id'] ?? null,
            'top_match'           => $response['data']['top_match'] ?? true,
            'phones'              => $queryData['phones'] ?? [],
            'names'               => $queryData['names'] ?? [],
            'emails'              => $queryData['emails'] ?? [],
            'jobs'                => $queryData['jobs'] ?? [],
            'urls'                => $queryData['urls'] ?? [],
            'images'              => $queryData['images'] ?? [],
            'presencia_redes'     => [
                ['red' => 'Teléfono Registrado', 'encontrado' => !empty($queryData['phones'])],
                ['red' => 'Correo Electrónico', 'encontrado' => !empty($queryData['emails'])],
                ['red' => 'Perfiles Digitales (URLs)', 'encontrado' => !empty($queryData['urls'])],
            ],
            'score_confiabilidad' => 95,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'status' => 'success',
            'search_id' => 'MOCK-SEARCH-987654321',
            'top_match' => true,
            'phones' => [
                ['display_international' => '+52 ' . ($subject->phone ?? '622 106 9217')]
            ],
            'names' => [
                ['display' => strtoupper($subject->name_or_company)]
            ],
            'emails' => [
                ['display' => $subject->email ?? 'contacto@ejemplo.com']
            ],
            'jobs' => [],
            'urls' => [],
            'images' => [],
            'presencia_redes' => [
                ['red' => 'Teléfono Registrado', 'encontrado' => true],
                ['red' => 'Correo Electrónico', 'encontrado' => true],
                ['red' => 'Perfiles Digitales (URLs)', 'encontrado' => false],
            ],
            'score_confiabilidad' => 95,
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        $phoneRaw = preg_replace('/[^0-9]/', '', $subject->phone ?? '6221069217');
        if (strlen($phoneRaw) === 10) {
            $phoneRaw = '52' . $phoneRaw;
        }

        return [
            'telefono' => $phoneRaw ?: '526221069217',
        ];
    }
}
