<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

class NufiSatListasConnector extends NufiConnector
{
    protected string $apiKeyCategory = 'enrichment';

    public function getIdentifier(): string
    {
        return 'sat_listas';
    }

    public function getName(): string
    {
        return 'Listas SAT 69 y 69B (EFOS/EDOS)';
    }

    public function appliesTo(Subject $subject): bool
    {
        // SAT Listas applies to all subjects
        return !empty($subject->rfc);
    }

    protected function callApi(Subject $subject): array
    {
        // 1. Query Article 69 (No Localizados)
        $response69 = $this->postRequest('/contribuyentes_69/v1/no_localizados', [
            'rfc' => $subject->rfc,
        ]);
        $log69 = $this->lastLog;

        $enLista69 = false;
        $oficioOficial69 = null;
        $fechaPublicacion69 = null;

        if (is_array($response69) && isset($response69['data']) && is_array($response69['data']) && count($response69['data']) > 0) {
            $enLista69 = true;
            $first69 = $response69['data'][0];
            $oficioOficial69 = $first69['oficio'] ?? $first69['oficio_oficial'] ?? $first69['numero_oficio'] ?? null;
            $fechaPublicacion69 = $first69['publicacion_dof'] ?? $first69['fecha_publicacion'] ?? $first69['fecha'] ?? null;
        }

        // 2. Query Article 69-B (Boletinados - EFOS/EDOS)
        $response69b = $this->postRequest('/contribuyentes/v1/obtener_contribuyente', [
            'rfc' => $subject->rfc,
        ]);
        $log69b = $this->lastLog;

        $enLista69b = false;
        $estatus69b = null;
        $oficioOficial69b = null;
        $fechaPublicacion69b = null;

        if (is_array($response69b) && isset($response69b['data']) && is_array($response69b['data']) && count($response69b['data']) > 0) {
            $enLista69b = true;
            $first69b = $response69b['data'][0];
            $estatus69b = $first69b['situacion_contribuyente'] ?? null;

            // Extract official notice
            foreach ([
                'fecha_oficio_global_definitivos',
                'fecha_oficio_global_presuncion',
                'fecha_oficio_global_desvirtuaron',
                'fecha_oficio_global_favorable'
            ] as $key) {
                if (!empty($first69b[$key])) {
                    $oficioOficial69b = $first69b[$key];
                    break;
                }
            }

            // Extract publication date
            foreach ([
                'fecha_publi_dof_definitivos',
                'fecha_publi_dof_presuntos',
                'fecha_publi_dof_desvirtuaron',
                'fecha_publi_dof_favorable',
                'fecha_publi_sat_definitivos',
                'fecha_publi_sat_presuntos',
                'fecha_publi_sat_desvirtuados',
                'fecha_publi_sat_favorable'
            ] as $key) {
                if (!empty($first69b[$key])) {
                    $fechaPublicacion69b = $first69b[$key];
                    break;
                }
            }
        }

        // Combine metadata prioritizing 69-B (since it is higher risk)
        $oficioOficial = $oficioOficial69b ?: $oficioOficial69;
        $fechaPublicacion = $fechaPublicacion69b ?: $fechaPublicacion69;

        // Consolidate logs for support
        $this->lastLog = [
            'url' => rtrim($this->baseUrl, '/') . '/contribuyentes (Consolidado SAT 69/69B)',
            'method' => 'POST',
            'headers' => $log69['headers'] ?? $log69b['headers'] ?? [],
            'body' => [
                'rfc' => $subject->rfc,
                'queries' => [
                    'no_localizados' => [
                        'url' => $log69['url'] ?? null,
                        'body' => $log69['body'] ?? null
                    ],
                    'obtener_contribuyente' => [
                        'url' => $log69b['url'] ?? null,
                        'body' => $log69b['body'] ?? null
                    ]
                ]
            ],
            'response' => [
                'status' => isset($log69b['response']['status']) ? $log69b['response']['status'] : (isset($log69['response']['status']) ? $log69['response']['status'] : 200),
                'body' => [
                    'no_localizados_response' => $response69,
                    'obtener_contribuyente_response' => $response69b,
                ]
            ]
        ];

        return [
            'rfc' => $subject->rfc,
            'en_lista_69' => $enLista69,
            'en_lista_69b' => $enLista69b,
            'estatus_69b' => $estatus69b,
            'oficio_oficial' => $oficioOficial,
            'fecha_publicacion' => $fechaPublicacion,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        // If the subject name contains 'Simulado' or 'Efo', let's mock them as being in list 69B for QA demonstration purposes.
        $in69b = (str_contains(strtolower($subject->name_or_company), 'simulado') || str_contains(strtolower($subject->name_or_company), 'efo'));
        // If the subject name contains 'No Localizado' or 'Inlocalizable', mock them in list 69.
        $in69 = (str_contains(strtolower($subject->name_or_company), 'no localizado') || str_contains(strtolower($subject->name_or_company), 'inlocalizable'));

        return [
            'rfc' => $subject->rfc,
            'en_lista_69' => $in69,
            'en_lista_69b' => $in69b,
            'estatus_69b' => $in69b ? 'Presunto' : null,
            'oficio_oficial' => ($in69b || $in69) ? '500-05-2026-OF-1024' : null,
            'fecha_publicacion' => ($in69b || $in69) ? now()->subMonths(3)->format('Y-m-d') : null,
        ];
    }
}
