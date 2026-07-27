<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

class NufiIdentidadDigitalConnector extends NufiConnector
{
    protected string $apiKeyCategory = 'enrichment';

    public function getIdentifier(): string
    {
        return 'identidad_digital';
    }

    public function getName(): string
    {
        return 'Identidad Digital y Búsqueda por Email';
    }

    public function appliesTo(Subject $subject): bool
    {
        return !empty($subject->email);
    }

    protected function callApi(Subject $subject): array
    {
        return $this->postRequest('/identidad_digital/v1/search', [
            'email' => $subject->email,
            'name'  => $subject->name_or_company,
        ]);
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'status' => 'success',
            'mensaje' => '[MOCK] Búsqueda de Identidad Digital finalizada.',
            'email_analizado' => $subject->email,
            'perfiles_encontrados' => 3,
            'presencia_redes' => [
                ['red' => 'LinkedIn', 'encontrado' => true, 'confianza' => 'Alta'],
                ['red' => 'GitHub', 'encontrado' => true, 'confianza' => 'Alta'],
                ['red' => 'Google Business', 'encontrado' => true, 'confianza' => 'Media'],
            ],
            'brechas_seguridad' => [
                'registrado_en_brechas' => false,
                'detalles' => 'No se encontraron filtraciones de credenciales vinculadas a este correo.',
            ],
            'score_confiabilidad' => 95,
        ];
    }
}
