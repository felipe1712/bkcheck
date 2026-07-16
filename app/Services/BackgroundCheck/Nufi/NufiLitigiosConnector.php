<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

class NufiLitigiosConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'litigios';
    }

    public function getName(): string
    {
        return 'Búsqueda de Litigios y Juicios';
    }

    public function appliesTo(Subject $subject): bool
    {
        // Litigios search applies to all subjects
        return !empty($subject->name_or_company);
    }

    protected function callApi(Subject $subject): array
    {
        $response = $this->postRequest('/litigios/v1/buscar', [
            'nombre' => $subject->name_or_company,
        ]);

        $juicios = [];
        $data = $response['data'] ?? $response ?? [];
        if (is_array($data)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    $juicios[] = [
                        'expediente' => $item['expediente'] ?? 'N/A',
                        'juzgado' => $item['juzgado'] ?? 'N/A',
                        'fuero' => $item['fuero'] ?? 'Local',
                        'materia' => $item['materia'] ?? 'Civil',
                        'actor' => $item['actor'] ?? 'N/A',
                        'demandado' => $item['demandado'] ?? 'N/A',
                        'fecha' => $item['fecha'] ?? null,
                    ];
                }
            }
        }

        return [
            'tiene_juicios' => count($juicios) > 0,
            'juicios' => $juicios,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        $name = strtolower($subject->name_or_company);
        $tieneJuicios = str_contains($name, 'litigio') || str_contains($name, 'juicio') || str_contains($name, 'demanda') || str_contains($name, 'efo');

        $juicios = [];
        if ($tieneJuicios) {
            $juicios[] = [
                'expediente' => rand(100, 999) . '/' . now()->format('Y'),
                'juzgado' => 'Juzgado Décimo de lo Civil de la CDMX',
                'fuero' => 'Local',
                'materia' => 'Mercantil',
                'actor' => 'BANCO MERCANTIL DEL NORTE S.A.',
                'demandado' => strtoupper($subject->name_or_company),
                'fecha' => now()->subMonths(18)->format('Y-m-d'),
            ];
            $juicios[] = [
                'expediente' => rand(1000, 9999) . '/2023',
                'juzgado' => 'Junta Especial Número Tres de la Local de Conciliación y Arbitraje',
                'fuero' => 'Laboral',
                'materia' => 'Laboral (Despido Injustificado)',
                'actor' => 'EX-EMPLEADO FICTICIO',
                'demandado' => strtoupper($subject->name_or_company),
                'fecha' => now()->subYears(2)->format('Y-m-d'),
            ];
        }

        return [
            'tiene_juicios' => $tieneJuicios,
            'juicios' => $juicios,
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return [
            'nombre' => $subject->name_or_company,
        ];
    }
}
