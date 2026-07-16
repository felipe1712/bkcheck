<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

class NufiMarcasConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'marcas';
    }

    public function getName(): string
    {
        return 'Búsqueda de Marcas IMPI';
    }

    public function appliesTo(Subject $subject): bool
    {
        // Marcas lookup applies to all subjects
        return !empty($subject->name_or_company);
    }

    protected function callApi(Subject $subject): array
    {
        $endpoint = '/trademark/v1/find?Ocp-Apim-Subscription-Key=' . urlencode($this->apiKey);

        $response = $this->postRequest($endpoint, [
            'name' => $subject->name_or_company,
        ]);

        $marcasList = [];
        $data = $response['data'] ?? $response ?? [];
        
        // If it's a single item wrap it in array
        if (is_array($data) && (isset($data['name']) || isset($data['denominacion']))) {
            $data = [$data];
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    $expediente = $item['numero_expediente'] ?? $item['expediente'] ?? null;
                    $registro = $item['numero_registro'] ?? $item['registro'] ?? null;
                    $denominacion = $item['denominacion'] ?? $item['name'] ?? $item['nombre'] ?? null;
                    $titular = $item['titular'] ?? $item['owner'] ?? $item['titulares'] ?? null;
                    $claseNice = $item['clase_nice'] ?? $item['clase'] ?? $item['class'] ?? null;
                    $fechaConcesion = $item['fecha_concesion'] ?? $item['concession_date'] ?? $item['fecha_registro'] ?? null;
                    $estatus = $item['estatus'] ?? $item['status'] ?? 'REGISTRADA';

                    $marcasList[] = [
                        'numero_expediente' => $expediente,
                        'numero_registro' => $registro,
                        'denominacion' => $denominacion,
                        'titular' => $titular,
                        'clase_nice' => $claseNice,
                        'fecha_concesion' => $fechaConcesion,
                        'estatus' => $estatus,
                    ];
                }
            }
        }

        return [
            'marcas' => $marcasList,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'marcas' => [
                [
                    'numero_expediente' => (string)rand(2000000, 2900000),
                    'numero_registro' => (string)rand(1000000, 1900000),
                    'denominacion' => strtoupper($subject->name_or_company),
                    'titular' => strtoupper($subject->name_or_company),
                    'fecha_presentacion' => now()->subYears(3)->format('Y-m-d'),
                    'fecha_concesion' => now()->subYears(2)->subMonths(6)->format('Y-m-d'),
                    'clase_nice' => 35,
                    'estatus' => 'REGISTRADA',
                ]
            ]
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return [
            'name' => $subject->name_or_company,
        ];
    }
}
