<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Validación CURP / RENAPO.
 *
 * Verifica que la CURP del sujeto sea válida y coincida con los registros
 * del Registro Nacional de Población (RENAPO) a través de la API de NuFi.
 *
 * Aplica únicamente a personas físicas con CURP registrada.
 * Endpoint NuFi esperado: POST /curp/valida o equivalente.
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

    public function appliesTo(Subject $subject): bool
    {
        return $subject->tipo === 'persona_fisica' && !empty($subject->curp);
    }

    protected function callApi(Subject $subject): array
    {
        $response = $this->postRequest('/curp/valida', [
            'curp' => strtoupper($subject->curp),
        ]);

        return $this->normalizar($response, $subject);
    }

    protected function mockResponse(Subject $subject): array
    {
        $curp = strtoupper($subject->curp ?? 'XXXX000000XXXXXX00');
        return [
            'curp'              => $curp,
            'valida'            => true,
            'nombre'            => 'JUAN',
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
        return ['curp' => strtoupper($subject->curp ?? '')];
    }

    private function normalizar(array $response, Subject $subject): array
    {
        return [
            'curp'              => $response['curp']             ?? $subject->curp,
            'valida'            => $response['valida']           ?? ($response['estatus'] === 'AN'),
            'nombre'            => $response['nombre']           ?? null,
            'primer_apellido'   => $response['primer_apellido']  ?? null,
            'segundo_apellido'  => $response['segundo_apellido'] ?? null,
            'fecha_nacimiento'  => $response['fecha_nacimiento'] ?? null,
            'sexo'              => $response['sexo']             ?? null,
            'estado_nacimiento' => $response['entidad_nacimiento'] ?? $response['estado_nacimiento'] ?? null,
            'nacionalidad'      => $response['nacionalidad']     ?? null,
            'estatus_curp'      => $response['estatus_curp']     ?? $response['estatus'] ?? null,
            'mensaje'           => $response['mensaje']          ?? null,
        ];
    }
}
