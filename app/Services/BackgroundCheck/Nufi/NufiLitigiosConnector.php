<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Antecedentes Judiciales y Litigios (Persona Física Nacional).
 *
 * Endpoint Oficial NuFi: POST /antecedentes_judiciales/v2/persona_fisica_nacional
 *
 * Reglas de los Campos:
 * - nombre: Nombre(s) de la persona física.
 * - paterno: Apellido paterno (requerido).
 * - materno: Apellido materno (requerido). En caso de no contar con él, enviar un espacio en blanco: " ".
 * - detalle: boolean (default true para incluir acuerdos).
 * - estado: string ("nacional" por defecto o abreviatura de entidad).
 */
class NufiLitigiosConnector extends NufiConnector
{
    protected string $apiKeyCategory = 'judicial';

    public function getIdentifier(): string
    {
        return 'litigios';
    }

    public function getName(): string
    {
        return 'Antecedentes Judiciales y Litigios';
    }

    public function getMinTierLevel(): int
    {
        return 2;
    }

    public function appliesTo(Subject $subject): bool
    {
        return !empty($subject->name_or_company);
    }

    /**
     * Sanitiza el texto dejando únicamente letras (incluyendo acentos) y espacios.
     */
    private function cleanName(?string $text): string
    {
        if (empty($text)) {
            return '';
        }
        $cleaned = preg_replace('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]/u', '', $text);
        return trim(preg_replace('/\s+/', ' ', $cleaned));
    }

    protected function callApi(Subject $subject): array
    {
        // 1. Extraer o dividir Nombre, Paterno y Materno
        $rawName = $this->cleanName($subject->name_or_company);

        if (!empty($subject->first_name) || !empty($subject->father_surname)) {
            $nombre  = strtoupper($this->cleanName($subject->first_name ?: $rawName));
            $paterno = strtoupper($this->cleanName($subject->father_surname ?: ''));
            $materno = !empty($subject->mother_surname) ? strtoupper($this->cleanName($subject->mother_surname)) : ' ';
        } else {
            // Dividir el nombre completo en partes
            $parts = array_values(array_filter(explode(' ', $rawName)));
            $count = count($parts);

            if ($count >= 4) {
                $nombre  = strtoupper($parts[0] . ' ' . $parts[1]);
                $paterno = strtoupper($parts[2]);
                $materno = strtoupper(implode(' ', array_slice($parts, 3)));
            } elseif ($count === 3) {
                $nombre  = strtoupper($parts[0]);
                $paterno = strtoupper($parts[1]);
                $materno = strtoupper($parts[2]);
            } elseif ($count === 2) {
                $nombre  = strtoupper($parts[0]);
                $paterno = strtoupper($parts[1]);
                $materno = ' '; // Espacio en blanco obligatorio si no tiene materno
            } else {
                $nombre  = strtoupper($parts[0] ?? $rawName);
                $paterno = strtoupper($parts[0] ?? $rawName);
                $materno = ' '; // Espacio en blanco obligatorio
            }
        }

        if (empty($paterno)) {
            $paterno = $nombre;
        }
        if (empty($materno)) {
            $materno = ' ';
        }

        $payload = [
            'nombre'  => $nombre,
            'paterno' => $paterno,
            'materno' => $materno,
            'detalle' => true,
            'estado'  => 'nacional',
        ];

        $response = $this->postRequest('/antecedentes_judiciales/v2/persona_fisica_nacional', $payload);

        $juicios = [];
        $data = $response['data'] ?? $response['resultados'] ?? $response['expedientes'] ?? (is_array($response) ? $response : []);
        if (is_array($data)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    $juicios[] = [
                        'expediente' => $item['expediente'] ?? $item['numero'] ?? 'N/A',
                        'juzgado'    => $item['juzgado'] ?? $item['tribunal'] ?? 'N/A',
                        'fuero'      => $item['fuero'] ?? 'Nacional / Local',
                        'materia'    => $item['materia'] ?? $item['tipo'] ?? 'Civil / Penal / Mercantil',
                        'actor'      => $item['actor'] ?? $item['demandante'] ?? 'N/A',
                        'demandado'  => $item['demandado'] ?? 'N/A',
                        'fecha'      => $item['fecha'] ?? $item['fecha_acuerdo'] ?? null,
                        'acuerdos'   => $item['acuerdos'] ?? [],
                    ];
                }
            }
        }

        return [
            'tiene_juicios' => count($juicios) > 0,
            'juicios'       => $juicios,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        $name = strtolower($subject->name_or_company);
        $tieneJuicios = str_contains($name, 'litigio') || str_contains($name, 'juicio') || str_contains($name, 'demanda') || str_contains($name, 'duarte');

        $juicios = [];
        if ($tieneJuicios) {
            $juicios[] = [
                'expediente' => rand(100, 999) . '/' . now()->format('Y'),
                'juzgado'    => 'Juzgado Décimo de lo Civil de la CDMX',
                'fuero'      => 'Local',
                'materia'    => 'Mercantil',
                'actor'      => 'BANCO MERCANTIL DEL NORTE S.A.',
                'demandado'  => strtoupper($this->cleanName($subject->name_or_company)),
                'fecha'      => now()->subMonths(18)->format('Y-m-d'),
                'acuerdos'   => ['Auto de radicación', 'Notificación a partes'],
            ];
            $juicios[] = [
                'expediente' => rand(1000, 9999) . '/2023',
                'juzgado'    => 'Junta Especial Número Tres de Conciliación y Arbitraje',
                'fuero'      => 'Laboral',
                'materia'    => 'Laboral (Despido Injustificado)',
                'actor'      => 'EX-EMPLEADO FICTICIO',
                'demandado'  => strtoupper($this->cleanName($subject->name_or_company)),
                'fecha'      => now()->subYears(2)->format('Y-m-d'),
                'acuerdos'   => ['Audiencia de desahogo de pruebas'],
            ];
        }

        return [
            'tiene_juicios' => $tieneJuicios,
            'juicios'       => $juicios,
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        $rawName = $this->cleanName($subject->name_or_company);
        $parts = array_values(array_filter(explode(' ', $rawName)));
        $nombre  = strtoupper($parts[0] ?? $rawName);
        $paterno = strtoupper($parts[1] ?? $rawName);
        $materno = isset($parts[2]) ? strtoupper($parts[2]) : ' ';

        return [
            'nombre'  => $nombre,
            'paterno' => $paterno,
            'materno' => $materno,
            'detalle' => true,
            'estado'  => 'nacional',
        ];
    }
}
