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
            'curp'                    => $curp,
            'valida'                  => true,
            'nombre'                  => $subject->name_or_company ?? 'JUAN',
            'primer_apellido'         => 'PÉREZ',
            'segundo_apellido'        => 'GARCÍA',
            'fecha_nacimiento'        => '1990-01-15',
            'sexo'                    => 'HOMBRE',
            'estado_nacimiento'       => 'CIUDAD DE MÉXICO',
            'nacionalidad'            => 'MEXICANA',
            'estatus_curp'            => 'AN',   // AN=Activo-Normal
            'description_status_curp' => 'Registro de Cambio No Afectando a CURP',
            'mensaje'                 => '[MOCK] CURP válida y activa en RENAPO.',
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
        // Handle nested response structures safely: body.data.curpdata.0 or data.curpdata.0 or root
        $body = $response['body'] ?? $response;
        $data = $body['data'] ?? $response['data'] ?? $body;

        $curpList = $data['curpdata'] ?? $data['curp_data'] ?? [];
        $curpItem = (!empty($curpList) && is_array($curpList) && isset($curpList[0])) ? $curpList[0] : (is_array($data) ? $data : []);

        $statusSuccess = ($body['status'] ?? $response['status'] ?? null) === 'success'
            || ($body['code'] ?? $response['code'] ?? null) === 200;

        $estatusCurp = $curpItem['statusCurp']
            ?? $curpItem['estatus_curp']
            ?? $curpItem['estatus']
            ?? $data['estatus']
            ?? $response['estatus']
            ?? null;

        $curpVal = $curpItem['curp'] ?? $data['curp'] ?? $response['curp'] ?? $subject->curp;
        $nombre = $curpItem['nombres'] ?? $curpItem['nombre'] ?? $data['nombres'] ?? $data['nombre'] ?? $response['nombre'] ?? null;
        $primerApellido = $curpItem['primerApellido'] ?? $curpItem['primer_apellido'] ?? $data['primer_apellido'] ?? $response['primer_apellido'] ?? null;
        $segundoApellido = $curpItem['segundoApellido'] ?? $curpItem['segundo_apellido'] ?? $data['segundo_apellido'] ?? $response['segundo_apellido'] ?? null;
        $fechaNacimiento = $curpItem['fechaNacimiento'] ?? $curpItem['fecha_nacimiento'] ?? $data['fecha_nacimiento'] ?? $response['fecha_nacimiento'] ?? null;
        $sexo = $curpItem['sexo'] ?? $data['sexo'] ?? $response['sexo'] ?? null;
        $estadoNac = $curpItem['entidad'] ?? $curpItem['estado_nacimiento'] ?? $data['entidad'] ?? $data['estado_nacimiento'] ?? $response['estado_nacimiento'] ?? null;
        $nacionalidad = $curpItem['nacionalidad'] ?? $data['nacionalidad'] ?? $response['nacionalidad'] ?? null;
        $descStatus = $curpItem['descriptionStatusCurp'] ?? $data['descriptionStatusCurp'] ?? null;

        $isValid = $response['valida'] ?? ($statusSuccess || !empty($curpVal));

        return [
            'curp'                    => $curpVal,
            'valida'                  => $isValid,
            'nombre'                  => $nombre,
            'primer_apellido'         => $primerApellido,
            'segundo_apellido'        => $segundoApellido,
            'fecha_nacimiento'        => $fechaNacimiento,
            'sexo'                    => $sexo,
            'estado_nacimiento'       => $estadoNac,
            'nacionalidad'            => $nacionalidad,
            'estatus_curp'            => $estatusCurp,
            'description_status_curp' => $descStatus,
            'mensaje'                 => $body['message'] ?? $response['message'] ?? $response['mensaje'] ?? 'Consulta RENAPO procesada',
        ];
    }
}
