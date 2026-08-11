<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Listas Negras Internacionales, PEPs, AML y OFAC Sancionados.
 *
 * Endpoints Oficiales NuFi:
 * 1. POST /perfilamiento/v1/aml
 *    Sanitización estricta: Solo letras y espacios ^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]*$
 * 2. POST /v1/sancionados_ofac/consultar
 *    Filtros oficiales: tipo=All, programa=All, puntaje_minimo_nombre=90, pais=All, lista=All
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
        return 'Listas Negras Internacionales (AML / PEPs / OFAC)';
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
        $hits = [];

        // 1. CONSULTA PERFILAMIENTO AML (/perfilamiento/v1/aml)
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

        $amlPayload = [
            'nombre_completo' => $fullNameClean,
            'primer_nombre'   => $primerNombre,
            'segundo_nombre'  => $segundoNombre,
            'apellidos'       => $apellidos,
        ];

        try {
            $amlResponse = $this->postRequest('/perfilamiento/v1/aml', $amlPayload);
            $rawAml = $amlResponse['body'] ?? $amlResponse;
            $codeAml = (int)($rawAml['code'] ?? $amlResponse['code'] ?? 200);
            $statusAml = strtolower($rawAml['status'] ?? $amlResponse['status'] ?? '');

            // Si devuelve código 204 o status "no_result" -> no hay coincidencias
            if ($codeAml !== 204 && $statusAml !== 'no_result') {
                $amlData = $rawAml['data'] ?? $rawAml['hits'] ?? $rawAml['resultados'] ?? null;
                if (is_array($amlData) && !empty($amlData)) {
                    foreach ($amlData as $item) {
                        // Asegurar que $item es un registro individual de coincidencia y no la estructura global del response
                        if (is_array($item) && !isset($item['code']) && !isset($item['status']) && (isset($item['nombre']) || isset($item['nombre_completo']) || isset($item['matched_name']) || isset($item['lista']))) {
                            $hits[] = [
                                'lista'             => $item['lista'] ?? $item['list_name'] ?? 'Listas AML / PEPs',
                                'nombre_encontrado' => $item['nombre_completo'] ?? $item['nombre'] ?? $item['matched_name'] ?? $fullNameClean,
                                'entidad_pais'      => $item['pais'] ?? $item['entidad'] ?? $item['country'] ?? 'MÉXICO',
                                'tipo_lista'        => $item['tipo'] ?? $item['type'] ?? 'PEP / Sanción AML',
                                'fecha_publicacion' => $item['fecha_publicacion'] ?? $item['date'] ?? null,
                                'comentarios'       => $item['comentarios'] ?? $item['detalle'] ?? $item['summary'] ?? '',
                            ];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently capture AML exception to attempt OFAC check
        }

        // 2. CONSULTA ESPECÍFICA OFAC SANCIONADOS (/v1/sancionados_ofac/consultar)
        $ofacPayload = [
            'tipo'                  => 'ALL',
            'nombre'                => $fullNameClean,
            'id'                    => '',
            'programa'              => 'ALL',
            'puntaje_minimo_nombre' => 90,
            'direccion'             => '',
            'ciudad'                => '',
            'estado'                => '',
            'pais'                  => 'ALL',
            'lista'                 => 'ALL',
        ];

        try {
            $ofacResponse = $this->postRequest('/v1/sancionados_ofac/consultar', $ofacPayload);
            $rawOfac = $ofacResponse['body'] ?? $ofacResponse;
            $codeOfac = (int)($rawOfac['code'] ?? $ofacResponse['code'] ?? 200);
            $statusOfac = strtolower($rawOfac['status'] ?? $ofacResponse['status'] ?? '');

            // Si devuelve código 204 o status "no_result" -> no hay coincidencias
            if ($codeOfac !== 204 && $statusOfac !== 'no_result') {
                $ofacData = $rawOfac['data'] ?? $rawOfac['resultados'] ?? $rawOfac['sancionados'] ?? null;
                if (is_array($ofacData) && !empty($ofacData)) {
                    foreach ($ofacData as $item) {
                        // Asegurar que $item es un registro individual de coincidencia y no la estructura global del response
                        if (is_array($item) && !isset($item['code']) && !isset($item['status']) && (isset($item['nombre']) || isset($item['nombre_completo']) || isset($item['sdn_name']) || isset($item['lista']))) {
                            $hits[] = [
                                'lista'             => $item['lista'] ?? $item['list_name'] ?? 'OFAC - Specially Designated Nationals (SDN)',
                                'nombre_encontrado' => $item['nombre'] ?? $item['nombre_completo'] ?? $item['sdn_name'] ?? $fullNameClean,
                                'entidad_pais'      => $item['pais'] ?? $item['country'] ?? 'EE.UU. / Internacional',
                                'tipo_lista'        => 'OFAC ' . ($item['programa'] ?? $item['program'] ?? 'SDN List'),
                                'fecha_publicacion' => $item['fecha'] ?? $item['date'] ?? null,
                                'comentarios'       => $item['comentarios'] ?? $item['remarks'] ?? (isset($item['score']) ? "Score de Coincidencia: {$item['score']}%" : ''),
                            ];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently capture OFAC exception
        }

        return [
            'encontrado' => count($hits) > 0,
            'hits'       => $hits,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        $name = strtolower($subject->name_or_company);
        $encontrado = str_contains($name, 'pep') || str_contains($name, 'sancionado') || str_contains($name, 'terrorista') || str_contains($name, 'zazueta') || str_contains($name, 'rodrigues');

        $hits = [];
        if ($encontrado) {
            $hits[] = [
                'lista'             => str_contains($name, 'pep') ? 'Personas Expuestas Políticamente (PEP México)' : 'OFAC - Specially Designated Nationals (SDN)',
                'nombre_encontrado' => strtoupper($this->cleanString($subject->name_or_company)),
                'entidad_pais'      => 'MÉXICO / EE.UU.',
                'tipo_lista'        => str_contains($name, 'pep') ? 'PEP' : 'OFAC SDN List (Score 90%+)',
                'fecha_publicacion' => now()->subMonths(6)->format('Y-m-d'),
                'comentarios'       => str_contains($name, 'pep') 
                    ? 'Identificado como familiar directo de funcionario de primer nivel en administración pública.'
                    : 'Coincidencia localizada en lista OFAC / Sancionados Internacionales.',
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
        return [
            'tipo'                  => 'ALL',
            'nombre'                => $fullNameClean,
            'id'                    => '',
            'programa'              => 'ALL',
            'puntaje_minimo_nombre' => 90,
            'direccion'             => '',
            'ciudad'                => '',
            'estado'                => '',
            'pais'                  => 'ALL',
            'lista'                 => 'ALL',
        ];
    }
}
