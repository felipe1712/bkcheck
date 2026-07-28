<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Validación CURP / RENAPO.
 *
 * Verifica que la CURP del sujeto sea válida y coincida con los registros
 * del Registro Nacional de Población (RENAPO) a través de la API de NuFi.
 *
 * Endpoint Oficial NuFi: POST /curp/v1/consulta
 */
class NufiCurpConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'curp';
    }

    public function getName(): string
    {
        return 'Validación CURP / RENAPO';
    }

    public function getMinTierLevel(): int
    {
        return 1;
    }

    public function appliesTo(Subject $subject): bool
    {
        return $subject->tipo === 'persona_fisica' && (!empty($subject->curp) || !empty($subject->name_or_company));
    }

    protected function callApi(Subject $subject): array
    {
        if (!empty($subject->curp)) {
            $payload = [
                'tipo_busqueda' => 'curp',
                'curp'          => strtoupper(trim($subject->curp)),
            ];
        } else {
            $payload = [
                'tipo_busqueda'    => 'datos',
                'nombres'          => $subject->name_or_company,
                'primer_apellido'  => '',
                'segundo_apellido' => '',
                'dia_nacimiento'   => '',
                'mes_nacimiento'   => '',
                'anio_nacimiento'  => '',
                'sexo'             => 'H',
                'clave_entidad'    => 'MN',
            ];
        }

        $response = $this->postRequest('/curp/v1/consulta', $payload);

        return $this->normalizar($response, $subject);
    }

    protected function mockResponse(Subject $subject): array
    {
        $curp = strtoupper($subject->curp ?? 'XXXX000000XXXXXX00');
        return [
            'curp'              => $curp,
            'valida'            => true,
            'nombre'            => $subject->name_or_company ?? 'JUAN',
            'primer_apellido'   => 'PÉREZ',
            'segundo_apellido'  => 'GARCÍA',
            'fecha_nacimiento'  => '1990-01-15',
            'sexo'              => 'HOMBRE',
            'estado_nacimiento' => 'CIUDAD DE MÉXICO',
            'nacionalidad'      => 'MEXICANA',
            'estatus_curp'      => 'AN',   // AN=Activo-Normal
            'mensaje'           => '[MOCK] CURP válida y activa en RENAPO.',
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        if (!empty($subject->curp)) {
            return [
                'tipo_busqueda' => 'curp',
                'curp'          => strtoupper(trim($subject->curp)),
            ];
        }
        return [
            'tipo_busqueda' => 'datos',
            'nombres'       => $subject->name_or_company,
        ];
    }

    private function normalizar(array $response, Subject $subject): array
    {
        return [
            'curp'              => $response['curp']             ?? $response['data']['curp'] ?? $subject->curp,
            'valida'            => $response['valida']           ?? ($response['estatus'] === 'AN' || ($response['status'] ?? null) === 'success'),
            'nombre'            => $response['nombre']           ?? $response['data']['nombres'] ?? null,
            'primer_apellido'   => $response['primer_apellido']  ?? $response['data']['primer_apellido'] ?? null,
            'segundo_apellido'  => $response['segundo_apellido'] ?? $response['data']['segundo_apellido'] ?? null,
            'fecha_nacimiento'  => $response['fecha_nacimiento']  ?? $response['data']['fecha_nacimiento'] ?? null,
            'sexo'              => $response['sexo']             ?? $response['data']['sexo'] ?? null,
            'estado_nacimiento' => $response['entidad_nacimiento'] ?? $response['estado_nacimiento'] ?? $response['data']['estado_nacimiento'] ?? null,
            'nacionalidad'      => $response['nacionalidad']     ?? $response['data']['nacionalidad'] ?? null,
            'estatus_curp'      => $response['estatus_curp']     ?? $response['estatus'] ?? $response['data']['estatus_curp'] ?? null,
            'mensaje'           => $response['mensaje']          ?? $response['message'] ?? null,
        ];
    }
}
