<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Antecedentes Penales (Fuero Común y Federal) vía NuFi.
 *
 * Endpoint configurable via `background_check.nufi.endpoints.antecedentes_penales`
 * (se ajustará el payload exacto con la documentación oficial de NuFi).
 */
class NufiPenalesConnector extends NufiConnector
{
    protected string $apiKeyCategory = 'judicial';

    public function getIdentifier(): string
    {
        return 'antecedentes_penales';
    }

    public function getName(): string
    {
        return 'Antecedentes Penales (Fuero Común y Federal)';
    }

    public function getMinTierLevel(): int
    {
        return 2;
    }

    public function appliesTo(Subject $subject): bool
    {
        return $subject->tipo === 'persona_fisica'
            && (!empty($subject->name_or_company) || !empty($subject->curp));
    }

    protected function callApi(Subject $subject): array
    {
        $endpoint = config('background_check.nufi.endpoints.antecedentes_penales', '/antecedentes_penales/v1/consulta');

        $payload = [
            'nombre'           => $subject->first_name ?? $subject->name_or_company ?? '',
            'primer_apellido'  => $subject->father_surname ?? '',
            'segundo_apellido' => $subject->mother_surname ?? '',
            'curp'             => $subject->curp ? strtoupper(trim($subject->curp)) : '',
            'rfc'              => $subject->rfc ? strtoupper(trim($subject->rfc)) : '',
        ];

        $response = $this->postRequest($endpoint, $payload);

        return $this->normalizar($response);
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'tiene_antecedentes' => false,
            'total_registros'    => 0,
            'fuero'              => 'Nacional (Común y Federal)',
            'registros'          => [],
            'estatus'            => 'Sin registros penales localizados',
            'fecha_consulta'     => now()->format('Y-m-d'),
            'mensaje'            => '[MOCK] Consulta de antecedentes penales ejecutada sin hallazgos.',
        ];
    }

    private function normalizar(array $response): array
    {
        $data = $response['data'] ?? $response;
        $registros = $data['registros'] ?? $data['antecedentes'] ?? [];
        if (!is_array($registros)) {
            $registros = [];
        }

        return [
            'tiene_antecedentes' => !empty($registros) || (bool) ($data['tiene_antecedentes'] ?? false),
            'total_registros'    => count($registros),
            'fuero'              => $data['fuero'] ?? 'Nacional',
            'registros'          => $registros,
            'estatus'            => !empty($registros) ? 'Con registros localizados' : 'Sin registros penales localizados',
            'fecha_consulta'     => now()->format('Y-m-d'),
            'mensaje'            => $response['message'] ?? 'Consulta de antecedentes penales completada.',
        ];
    }
}
