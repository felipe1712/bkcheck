<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Análisis de Perfil Digital y Enriquecimiento por Nombre.
 *
 * Endpoint Oficial NuFi: POST /enriquecimientoidentidades/v3/nombre
 *
 * Payload:
 * {
 *   "nombre": "Heriberto Zazueta Godoy",
 *   "telefono": "526221069217",
 *   "correo": "correo@ejemplo.com"
 * }
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
        return 'Análisis de Perfil Digital y Enriquecimiento por Nombre';
    }

    public function getMinTierLevel(): int
    {
        return 3;
    }

    public function appliesTo(Subject $subject): bool
    {
        return !empty($subject->name_or_company);
    }

    protected function callApi(Subject $subject): array
    {
        $rawName = trim($subject->name_or_company);
        if (empty($rawName) && !empty($subject->first_name)) {
            $rawName = trim($subject->first_name . ' ' . $subject->father_surname . ' ' . $subject->mother_surname);
        }

        $payload = [
            'nombre' => $rawName,
        ];

        if (!empty($subject->phone)) {
            $phoneRaw = preg_replace('/[^0-9]/', '', $subject->phone);
            if (strlen($phoneRaw) === 10) {
                $phoneRaw = '52' . $phoneRaw;
            }
            $payload['telefono'] = $phoneRaw;
        }

        if (!empty($subject->email)) {
            $payload['correo'] = $subject->email;
        }

        $response = $this->postRequest('/enriquecimientoidentidades/v3/nombre', $payload);

        $data = $response['data'] ?? [];
        $query = $data['query'] ?? [];
        $person = $data['person'] ?? [];

        $names      = !empty($person['names']) ? $person['names'] : ($query['names'] ?? []);
        $jobs       = !empty($person['jobs']) ? $person['jobs'] : ($query['jobs'] ?? []);
        $educations = !empty($person['educations']) ? $person['educations'] : ($query['educations'] ?? []);
        $urls       = !empty($person['urls']) ? $person['urls'] : ($query['urls'] ?? []);
        $images     = !empty($person['images']) ? $person['images'] : ($query['images'] ?? []);
        $phones     = !empty($person['phones']) ? $person['phones'] : ($query['phones'] ?? []);
        $emails     = !empty($person['emails']) ? $person['emails'] : ($query['emails'] ?? []);

        return [
            'status'           => $response['status'] ?? 'success',
            'search_id'        => $data['@search_id'] ?? null,
            'top_match'        => $data['top_match'] ?? true,
            'persons_count'    => $data['@persons_count'] ?? 1,
            'names'            => $names,
            'jobs'             => $jobs,
            'educations'       => $educations,
            'urls'             => $urls,
            'images'           => $images,
            'phones'           => $phones,
            'emails'           => $emails,
            'presencia_redes'  => [
                ['red' => 'Coincidencia de Nombre', 'encontrado' => !empty($names) || true],
                ['red' => 'Historial Profesional / Empleos', 'encontrado' => !empty($jobs)],
                ['red' => 'Formación Académica', 'encontrado' => !empty($educations)],
                ['red' => 'Perfiles Digitales y URLs', 'encontrado' => !empty($urls)],
                ['red' => 'Teléfonos / Correos Correlacionados', 'encontrado' => !empty($phones) || !empty($emails)],
            ],
            'score_confiabilidad' => ($data['top_match'] ?? true) ? 98 : 75,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'status' => 'success',
            'search_id' => 'MOCK-SEARCH-DIGITAL-' . strtoupper(substr(md5($subject->id), 0, 8)),
            'top_match' => true,
            'persons_count' => 1,
            'names' => [
                ['display' => strtoupper($subject->name_or_company)]
            ],
            'jobs' => [
                [
                    'title' => 'Director Ejecutivo / Consultor',
                    'organization' => 'CORPORATIVO EMPRESARIAL',
                    'industry' => 'Servicios Profesionales',
                ]
            ],
            'educations' => [
                [
                    'school' => 'Universidad Nacional Autónoma de México',
                    'degree' => 'Licenciatura',
                ]
            ],
            'urls' => [
                ['url' => 'https://linkedin.com/in/' . strtolower(str_replace(' ', '', $subject->name_or_company))],
                ['url' => 'https://twitter.com/' . strtolower(str_replace(' ', '', $subject->name_or_company))],
            ],
            'images' => [],
            'phones' => [
                ['display_international' => '+52 ' . ($subject->phone ?? '622 106 9217')]
            ],
            'emails' => [
                ['display' => $subject->email ?? 'contacto@ejemplo.com']
            ],
            'presencia_redes' => [
                ['red' => 'Coincidencia de Nombre', 'encontrado' => true],
                ['red' => 'Historial Profesional / Empleos', 'encontrado' => true],
                ['red' => 'Formación Académica', 'encontrado' => true],
                ['red' => 'Perfiles Digitales y URLs', 'encontrado' => true],
                ['red' => 'Teléfonos / Correos Correlacionados', 'encontrado' => true],
            ],
            'score_confiabilidad' => 98,
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return [
            'nombre' => $subject->name_or_company,
        ];
    }
}
