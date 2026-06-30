<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

class NufiCsdConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'csd';
    }

    public function getName(): string
    {
        return 'Certificados CSD y FIEL';
    }

    public function appliesTo(Subject $subject): bool
    {
        // CSD validation applies to all subjects
        return !empty($subject->rfc);
    }

    protected function callApi(Subject $subject): array
    {
        $webhookUrl = config('background_check.nufi.webhook_url') ?? url('/api/nufi/webhook');
        return $this->postRequest('/certificadosat/v1/consultar/async', [
            'rfc' => $subject->rfc,
            'webhook' => $webhookUrl,
        ]);
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'rfc' => $subject->rfc,
            'certificados' => [
                [
                    'numero_serie' => '000010000005' . rand(10000000, 99999999),
                    'estado' => 'ACTIVO',
                    'tipo' => 'CSD',
                    'fecha_inicio' => now()->subYears(2)->toIso8601String(),
                    'fecha_fin' => now()->addYears(2)->toIso8601String(),
                ],
                [
                    'numero_serie' => '000010000005' . rand(10000000, 99999999),
                    'estado' => 'CADUCO',
                    'tipo' => 'FIEL',
                    'fecha_inicio' => now()->subYears(6)->toIso8601String(),
                    'fecha_fin' => now()->subYears(2)->toIso8601String(),
                ]
            ]
        ];
    }
}
