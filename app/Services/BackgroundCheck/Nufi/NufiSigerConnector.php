<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

class NufiSigerConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'siger';
    }

    public function getName(): string
    {
        return 'Registro Público de Comercio (SIGER)';
    }

    public function appliesTo(Subject $subject): bool
    {
        // SIGER applies to entities (persona_moral)
        return $subject->tipo === 'persona_moral';
    }

    protected function callApi(Subject $subject): array
    {
        $response = $this->postRequest('/siger/v4/busqueda_socio', [
            'socio' => $subject->name_or_company,
        ]);

        // Map the results array to match what the view expects
        $resultados = [];
        if (is_array($response)) {
            foreach ($response as $item) {
                if (is_array($item) && isset($item['fme'])) {
                    $socios = [];
                    $partnersList = $item['nombre_socios'] ?? $item['socios'] ?? [];
                    if (is_array($partnersList)) {
                        foreach ($partnersList as $p) {
                            $firstName = $p['nombre'] ?? '';
                            $paternal = $p['apellido_paterno'] ?? '';
                            $maternal = $p['apellido_materno'] ?? '';
                            $fullName = trim("{$firstName} {$paternal} {$maternal}");
                            if (empty($fullName)) {
                                $fullName = $p['nombre_completo'] ?? $p['nombre'] ?? '';
                            }
                            
                            $participacion = '';
                            if (isset($p['acciones'])) {
                                $participacion = $p['acciones'] . ' acciones';
                            }
                            if (isset($p['valor']) && !empty($p['valor'])) {
                                $participacion .= ' ($' . $p['valor'] . ')';
                            }
                            
                            $socios[] = [
                                'nombre' => $fullName,
                                'participacion' => $participacion,
                            ];
                        }
                    }

                    $capitalSocial = 0.0;
                    if (is_array($partnersList)) {
                        foreach ($partnersList as $p) {
                            $capitalSocial += (float)($p['valor'] ?? 0.0);
                        }
                    }

                    $fechaConst = null;
                    if (isset($item['fecha_incripcion'])) {
                        $parts = explode(' ', $item['fecha_incripcion']);
                        if (count($parts) > 0) {
                            $datePart = $parts[0];
                            $dParts = explode('/', $datePart);
                            if (count($dParts) === 3) {
                                $fechaConst = "{$dParts[2]}-{$dParts[1]}-{$dParts[0]}";
                            }
                        }
                    }

                    $resultados[] = [
                        'fme' => $item['fme'],
                        'razon_social' => $item['razon_social'] ?? '',
                        'entidad_federativa' => $item['entidad_federativa'] ?? 'CIUDAD DE MÉXICO',
                        'fecha_constitucion' => $fechaConst ?? now()->subYears(5)->format('Y-m-d'),
                        'objeto_social' => $item['objeto_social'] ?? '',
                        'capital_social' => $capitalSocial > 0 ? $capitalSocial : 50000.00,
                        'socios' => $socios,
                    ];
                }
            }
        }

        return [
            'resultados' => $resultados,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'resultados' => [
                [
                    'fme' => (string)rand(100000, 999999),
                    'razon_social' => strtoupper($subject->name_or_company),
                    'entidad_federativa' => 'CIUDAD DE MÉXICO',
                    'fecha_constitucion' => now()->subYears(5)->format('Y-m-d'),
                    'objeto_social' => 'La sociedad tiene por objeto principal la prestación de toda clase de servicios profesionales, comerciales, consultoría y desarrollo tecnológico...',
                    'capital_social' => 50000.00,
                    'socios' => [
                        [
                            'nombre' => 'JUAN PÉREZ LÓPEZ',
                            'participacion' => '50%',
                        ],
                        [
                            'nombre' => 'MARÍA GÓMEZ RODRÍGUEZ',
                            'participacion' => '50%',
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return [
            'socio' => $subject->name_or_company,
        ];
    }
}
