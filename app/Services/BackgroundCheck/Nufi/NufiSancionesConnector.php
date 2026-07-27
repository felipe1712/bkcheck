<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

class NufiSancionesConnector extends NufiConnector
{
    protected string $apiKeyCategory = 'enrichment';

    public function getIdentifier(): string
    {
        return 'sanciones';
    }

    public function getName(): string
    {
        return 'Listas de Sanciones y PEPs';
    }

    public function appliesTo(Subject $subject): bool
    {
        // Sanciones check applies to all subjects with a name
        return !empty($subject->name_or_company);
    }

    protected function callApi(Subject $subject): array
    {
        $response = $this->postRequest('/listas/v1/buscar', [
            'nombre' => $subject->name_or_company,
        ]);

        $hits = [];
        $data = $response['data'] ?? $response ?? [];
        if (is_array($data)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    $hits[] = [
                        'lista' => $item['lista'] ?? 'Desconocida',
                        'nombre_encontrado' => $item['nombre_completo'] ?? $item['nombre'] ?? 'N/A',
                        'entidad_pais' => $item['pais'] ?? $item['entidad'] ?? 'N/A',
                        'tipo_lista' => $item['tipo'] ?? 'Sanción / Alerta',
                        'fecha_publicacion' => $item['fecha_publicacion'] ?? null,
                        'comentarios' => $item['comentarios'] ?? $item['detalle'] ?? '',
                    ];
                }
            }
        }

        return [
            'encontrado' => count($hits) > 0,
            'hits' => $hits,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        $name = strtolower($subject->name_or_company);
        $encontrado = str_contains($name, 'pep') || str_contains($name, 'sancionado') || str_contains($name, 'terrorista') || str_contains($name, 'efo');

        $hits = [];
        if ($encontrado) {
            $hits[] = [
                'lista' => str_contains($name, 'pep') ? 'Personas Expuestas Políticamente (PEP México)' : 'OFAC - Specially Designated Nationals (SDN)',
                'nombre_encontrado' => strtoupper($subject->name_or_company),
                'entidad_pais' => 'MÉXICO / USA',
                'tipo_lista' => str_contains($name, 'pep') ? 'PEP' : 'Sanción Internacional',
                'fecha_publicacion' => now()->subMonths(6)->format('Y-m-d'),
                'comentarios' => str_contains($name, 'pep') 
                    ? 'Identificado como familiar directo de funcionario de primer nivel en administración estatal.'
                    : 'Bloqueado por presuntas operaciones con recursos de procedencia ilícita.',
            ];
        }

        return [
            'encontrado' => $encontrado,
            'hits' => $hits,
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return [
            'nombre' => $subject->name_or_company,
        ];
    }
}
