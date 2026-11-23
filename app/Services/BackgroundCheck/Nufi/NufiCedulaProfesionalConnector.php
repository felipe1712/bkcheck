<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Cédula Profesional (SEP — Registro Nacional de Profesionistas) vía NuFi.
 *
 * Endpoint Oficial NuFi: POST /CedulaProfesional/consultar
 * Header: Ocp-Apim-Subscription-Key
 */
class NufiCedulaProfesionalConnector extends NufiConnector
{
    protected string $apiKeyCategory = 'enrichment';

    public function getIdentifier(): string
    {
        return 'cedula_profesional';
    }

    public function getName(): string
    {
        return 'Cédula Profesional (SEP)';
    }

    public function getMinTierLevel(): int
    {
        return 1;
    }

    public function appliesTo(Subject $subject): bool
    {
        return $subject->tipo === 'persona_fisica'
            && (!empty($subject->curp) || !empty($subject->name_or_company));
    }

    protected function callApi(Subject $subject): array
    {
        $payload = [];

        if (!empty($subject->curp)) {
            $payload['curp'] = strtoupper(trim($subject->curp));
        }
        if (!empty($subject->first_name)) {
            $payload['nombre'] = trim($subject->first_name);
        } elseif (!empty($subject->name_or_company) && !str_starts_with($subject->name_or_company, 'Por resolver')) {
            $payload['nombre'] = trim($subject->name_or_company);
        }
        if (!empty($subject->father_surname)) {
            $payload['apellido_paterno'] = trim($subject->father_surname);
        }
        if (!empty($subject->mother_surname)) {
            $payload['apellido_materno'] = trim($subject->mother_surname);
        }

        $response = $this->postRequest('/CedulaProfesional/consultar', $payload);

        return $this->normalizar($response);
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'tiene_cedula' => true,
            'total'        => 1,
            'cedulas'      => [
                [
                    'nombre'         => strtoupper($subject->name_or_company ?? 'SUJETO DE PRUEBA'),
                    'num_cedula'     => '8451290',
                    'profesion'      => 'LICENCIATURA EN DERECHO',
                    'genero'         => 'HOMBRE',
                    'institucion'    => 'UNIVERSIDAD NACIONAL AUTÓNOMA DE MÉXICO',
                    'fecha_registro' => '2014',
                    'tipo'           => 'C1',
                ],
            ],
            'mensaje'      => '[MOCK] Cédula profesional localizada en SEP.',
        ];
    }

    private function normalizar(array $response): array
    {
        $raw = $response['data'] ?? [];
        if (!is_array($raw)) {
            $raw = [];
        }

        $cedulas = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $cedulas[] = [
                    'nombre'         => $item['nombre'] ?? 'N/A',
                    'num_cedula'     => $item['numCedula'] ?? $item['num_cedula'] ?? 'N/A',
                    'profesion'      => $item['profesion'] ?? 'N/A',
                    'genero'         => $item['genero'] ?? null,
                    'institucion'    => $item['institucion'] ?? 'N/A',
                    'fecha_registro' => $item['fechaRegistro'] ?? $item['fecha_registro'] ?? null,
                    'tipo'           => $item['tipo'] ?? null,
                ];
            }
        }

        return [
            'tiene_cedula' => count($cedulas) > 0,
            'total'        => count($cedulas),
            'cedulas'      => $cedulas,
            'mensaje'      => $response['message'] ?? 'Consulta de Cédula Profesional completada.',
        ];
    }
}
