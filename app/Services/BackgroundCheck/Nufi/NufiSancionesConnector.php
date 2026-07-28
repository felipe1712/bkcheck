<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Listas Negras Internacionales, PEPs y AML.
 *
 * Endpoint Oficial NuFi: POST /perfilamiento/v1/aml
 * Sanitización estricta: Solo letras y espacios ^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]*$
 */
class NufiSancionesConnector extends NufiConnector
{
    protected string $apiKeyCategory = 'enrichment';

    public function getIdentifier(): string
    {
        return 'sanciones';
    }

    public function getName(): string
    {
        return 'Listas Negras Internacionales (PEPs / AML / OFAC)';
    }

    public function getMinTierLevel(): int
    {
        return 1;
    }

    public function appliesTo(Subject $subject): bool
    {
        return !empty($subject->name_or_company);
    }

    /**
     * Sanitiza cadenas eliminando puntuación, números y caracteres especiales,
     * dejando únicamente letras (incluyendo acentos/ñ) y espacios: ^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]*$
     */
    private function cleanString(?string $text): string
    {
        if (empty($text)) {
            return '';
        }
        $cleaned = preg_replace('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]/u', '', $text);
        return trim(preg_replace('/\s+/', ' ', $cleaned));
    }

    protected function callApi(Subject $subject): array
    {
        $fullNameClean = $this->cleanString($subject->name_or_company);

        // Separación inteligente de nombres
        $nameParts = array_values(array_filter(explode(' ', $fullNameClean)));
        $primerNombre = $this->cleanString($nameParts[0] ?? '');
        $segundoNombre = count($nameParts) > 2 ? $this->cleanString($nameParts[1] ?? '') : '';
        
        if (count($nameParts) > 2) {
            $apellidosArr = array_slice($nameParts, 2);
            $apellidos = $this->cleanString(implode(' ', $apellidosArr));
        } elseif (count($nameParts) === 2) {
            $apellidos = $this->cleanString($nameParts[1]);
        } else {
            $apellidos = '';
        }

        $payload = [
            'nombre_completo' => $fullNameClean,
            'primer_nombre'   => $primerNombre,
            'segundo_nombre'  => $segundoNombre,
            'apellidos'       => $apellidos,
        ];

        $response = $this->postRequest('/perfilamiento/v1/aml', $payload);

        $hits = [];
        $data = $response['data'] ?? $response['hits'] ?? $response['resultados'] ?? (is_array($response) ? $response : []);
        if (is_array($data)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    $hits[] = [
                        'lista'             => $item['lista'] ?? $item['list_name'] ?? 'Listas AML / Sanciones',
                        'nombre_encontrado' => $item['nombre_completo'] ?? $item['nombre'] ?? $item['matched_name'] ?? 'N/A',
                        'entidad_pais'      => $item['pais'] ?? $item['entidad'] ?? $item['country'] ?? 'N/A',
                        'tipo_lista'        => $item['tipo'] ?? $item['type'] ?? 'PEP / Sanción Internacional',
                        'fecha_publicacion' => $item['fecha_publicacion'] ?? $item['date'] ?? null,
                        'comentarios'       => $item['comentarios'] ?? $item['detalle'] ?? $item['summary'] ?? '',
                    ];
                }
            }
        }

        return [
            'encontrado' => count($hits) > 0,
            'hits'       => $hits,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        $name = strtolower($subject->name_or_company);
        $encontrado = str_contains($name, 'pep') || str_contains($name, 'sancionado') || str_contains($name, 'terrorista') || str_contains($name, 'zazueta');

        $hits = [];
        if ($encontrado) {
            $hits[] = [
                'lista'             => str_contains($name, 'pep') ? 'Personas Expuestas Políticamente (PEP México)' : 'OFAC - Specially Designated Nationals (SDN)',
                'nombre_encontrado' => strtoupper($this->cleanString($subject->name_or_company)),
                'entidad_pais'      => 'MÉXICO / USA',
                'tipo_lista'        => str_contains($name, 'pep') ? 'PEP' : 'Sanción Internacional AML',
                'fecha_publicacion' => now()->subMonths(6)->format('Y-m-d'),
                'comentarios'       => str_contains($name, 'pep') 
                    ? 'Identificado como familiar directo de funcionario de primer nivel en administración estatal.'
                    : 'Bloqueado por presuntas operaciones con recursos de procedencia ilícita (Listas AML).',
            ];
        }

        return [
            'encontrado' => $encontrado,
            'hits'       => $hits,
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        $fullNameClean = $this->cleanString($subject->name_or_company);
        $nameParts = array_values(array_filter(explode(' ', $fullNameClean)));
        $primerNombre = $this->cleanString($nameParts[0] ?? '');
        $segundoNombre = count($nameParts) > 2 ? $this->cleanString($nameParts[1] ?? '') : '';
        $apellidos = count($nameParts) >= 2 ? $this->cleanString(implode(' ', array_slice($nameParts, count($nameParts) > 2 ? 2 : 1))) : '';

        return [
            'nombre_completo' => $fullNameClean,
            'primer_nombre'   => $primerNombre,
            'segundo_nombre'  => $segundoNombre,
            'apellidos'       => $apellidos,
        ];
    }
}
