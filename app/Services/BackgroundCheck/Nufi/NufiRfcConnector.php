<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

class NufiRfcConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'rfc';
    }

    public function getName(): string
    {
        return 'Validación de RFC';
    }

    public function appliesTo(Subject $subject): bool
    {
        // RFC validation applies to all subjects
        return !empty($subject->rfc);
    }

    protected function callApi(Subject $subject): array
    {
        $response = $this->postRequest('/estatusrfc/valida', [
            'rfc' => $subject->rfc,
        ]);

        $message = $response['message'] ?? '';
        $isValid = stripos($message, 'válido') !== false || stripos($message, 'valido') !== false;

        return [
            'rfc' => $subject->rfc,
            'valido' => $isValid,
            'situacion' => $isValid ? 'ACTIVO' : 'INACTIVO',
            'razon_social' => $subject->name_or_company,
            'tipo_persona' => strlen($subject->rfc) === 12 ? 'MORAL' : 'FISICA',
            'detalle' => $message,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'rfc' => $subject->rfc,
            'valido' => true,
            'situacion' => 'ACTIVO',
            'razon_social' => $subject->name_or_company,
            'tipo_persona' => $subject->tipo === 'persona_moral' ? 'MORAL' : 'FISICA',
            'curp' => $subject->curp ?? ($subject->tipo === 'persona_fisica' ? 'MOCKCURP840502HDFLNR01' : null),
        ];
    }
}
