<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Listas SAT 69 (No Localizados) y 69-B (Boletinados / EFOS).
 *
 * Endpoints Oficiales NuFi:
 * 1. Lista 69: POST /contribuyentes_69/v1/no_localizados
 * 2. Lista 69-B: POST /contribuyentes/v1/obtener_contribuyente
 */
class NufiSatListasConnector extends NufiConnector
{
    protected string $apiKeyCategory = 'enrichment';

    public function getIdentifier(): string
    {
        return 'sat_listas';
    }

    public function getName(): string
    {
        return 'Listas SAT 69 y 69-B (Cumplimiento Fiscal)';
    }

    public function getMinTierLevel(): int
    {
        return 1;
    }

    public function appliesTo(Subject $subject): bool
    {
        return !empty($subject->rfc);
    }

    /**
     * Mapeo de definiciones legales oficiales según el estatus en Lista 69-B del SAT.
     */
    public static function getSituacionDetalle(?string $estatus): array
    {
        $key = strtoupper(trim($estatus ?? ''));
        
        $map = [
            'PRESUNTO' => [
                'estatus' => 'PRESUNTO',
                'badge_class' => 'warning',
                'descripcion' => 'PRESUNTO: La autoridad fiscal detectó que el contribuyente ha estado emitiendo comprobantes de manera irregular, presumiéndose la inexistencia de las operaciones amparadas en dichos comprobantes. Se notificó mediante buzón tributario, portal del SAT y DOF.'
            ],
            'DESVIRTUADO' => [
                'estatus' => 'DESVIRTUADO',
                'badge_class' => 'info',
                'descripcion' => 'DESVIRTUADO: El contribuyente aportó a la autoridad fiscal la documentación e información pertinente dentro del plazo de 15 días, logrando desvirtuar los hechos que llevaron a su notificación.'
            ],
            'DEFINITIVO' => [
                'estatus' => 'DEFINITIVO',
                'badge_class' => 'danger',
                'descripcion' => 'DEFINITIVO: El contribuyente no atendió el llamado de la autoridad en 15 días o no logró desvirtuar la inexistencia de sus operaciones, confirmando su publicación en el listado definitivo de simuladores (EFOS).'
            ],
            'SENTENCIA FAVORABLE' => [
                'estatus' => 'SENTENCIA FAVORABLE',
                'badge_class' => 'success',
                'descripcion' => 'SENTENCIA FAVORABLE: El contribuyente acreditó dentro de los 30 días la realidad de sus operaciones mediante resolución judicial o administrativa favorable.'
            ],
        ];

        return $map[$key] ?? [
            'estatus' => $estatus ?: 'REGISTRADO',
            'badge_class' => 'secondary',
            'descripcion' => 'El contribuyente se encuentra registrado en los listados oficiales del Artículo 69-B del SAT.'
        ];
    }

    protected function callApi(Subject $subject): array
    {
        $rfc = strtoupper(trim($subject->rfc));

        // 1. Consulta Artículo 69 (No Localizados / Incumplidos)
        $response69 = $this->postRequest('/contribuyentes_69/v1/no_localizados', [
            'rfc' => $rfc,
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

        // 2. Consulta Artículo 69-B (Boletinados EFOS - Simulación de Operaciones)
        $response69b = $this->postRequest('/contribuyentes/v1/obtener_contribuyente', [
            'rfc' => $rfc,
        ]);
        $log69b = $this->lastLog;

        $enLista69b = false;
        $estatus69b = null;
        $oficioOficial69b = null;
        $fechaPublicacion69b = null;
        $first69b = null;

        if (is_array($response69b) && isset($response69b['data']) && is_array($response69b['data']) && count($response69b['data']) > 0) {
            $enLista69b = true;
            $first69b = $response69b['data'][0];
            $estatus69b = $first69b['situacion_contribuyente'] ?? null;

            // Extraer oficio oficial
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

            // Extraer fecha publicación DOF / SAT
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

        $oficioOficial = $oficioOficial69b ?: $oficioOficial69;
        $fechaPublicacion = $fechaPublicacion69b ?: $fechaPublicacion69;
        $situacionDetalle = $enLista69b ? self::getSituacionDetalle($estatus69b) : null;

        // Consolidar logs
        $this->lastLog = [
            'url' => rtrim($this->baseUrl, '/') . '/contribuyentes (Consolidado SAT 69/69B)',
            'method' => 'POST',
            'headers' => $log69['headers'] ?? $log69b['headers'] ?? [],
            'body' => [
                'rfc' => $rfc,
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
                'status' => $log69b['response']['status'] ?? $log69['response']['status'] ?? 200,
                'body' => [
                    'no_localizados_response' => $response69,
                    'obtener_contribuyente_response' => $response69b,
                ]
            ]
        ];

        return [
            'rfc' => $rfc,
            'en_lista_69' => $enLista69,
            'en_lista_69b' => $enLista69b,
            'estatus_69b' => $estatus69b,
            'situacion_descripcion' => $situacionDetalle['descripcion'] ?? null,
            'situacion_badge_class' => $situacionDetalle['badge_class'] ?? 'warning',
            'oficio_oficial' => $oficioOficial,
            'fecha_publicacion' => $fechaPublicacion,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        $rfc = strtoupper(trim($subject->rfc ?? ''));
        $name = strtolower($subject->name_or_company);

        $in69b = str_contains($name, 'simulado') || str_contains($name, 'efo');
        $in69 = str_contains($name, 'no localizado') || str_contains($name, 'inlocalizable');

        $estatusMock = $in69b ? 'Definitivo' : null;
        $situacionDetalle = $in69b ? self::getSituacionDetalle($estatusMock) : null;

        return [
            'rfc' => $rfc,
            'en_lista_69' => $in69,
            'en_lista_69b' => $in69b,
            'estatus_69b' => $estatusMock,
            'situacion_descripcion' => $situacionDetalle['descripcion'] ?? null,
            'situacion_badge_class' => $situacionDetalle['badge_class'] ?? 'danger',
            'oficio_oficial' => ($in69b || $in69) ? '500-05-2018-22825 de fecha 17 de agosto de 2018' : null,
            'fecha_publicacion' => ($in69b || $in69) ? now()->subMonths(3)->format('Y-m-d') : null,
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return [
            'rfc' => strtoupper(trim($subject->rfc ?? '')),
        ];
    }
}
