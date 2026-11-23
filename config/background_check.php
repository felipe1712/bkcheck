<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Integration Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all settings, endpoints, and mock toggles for the
    | background check API connectors.
    |
    */

    'nufi' => [
        'base_url' => env('NUFI_BASE_URL', env('NUFI_API_URL', 'https://nufi.azure-api.net')),
        'api_key' => env('NUFI_API_KEY', ''),
        'api_key_general' => env('NUFI_API_KEY_GENERAL', '7ab9fd7a3bec4c88b08455fd0f1b9405'),
        'api_key_enrichment' => env('NUFI_API_KEY_ENRICHMENT', '60476115a2b14fc0948fed47cca07d65'),
        'api_key_judicial' => env('NUFI_API_KEY_JUDICIAL', '57e5493afbb14b9688bb6376af4d0999'),
        'mock' => env('NUFI_MOCK', false), // Live production/sandbox API mode by default
        'webhook_url' => env('NUFI_WEBHOOK_URL'),
        'endpoints' => [
            'antecedentes_penales' => env('NUFI_ENDPOINT_PENALES', '/antecedentes_penales/v1/consulta'),
            'buro'                 => env('NUFI_ENDPOINT_BURO', '/buro/consulta'),
        ],
    ],

    // Estimación de costos ficticios internos por API para tableros analíticos
    'costs' => [
        'rfc'                 => 0.50,
        'csd'                 => 1.20,
        'siger'               => 3.50,
        'sat_listas'          => 0.40,
        'marcas'              => 2.00,
        'ine_frente'          => 1.50,
        'ine_reverso'         => 1.50,
        'lista_nominal'       => 1.50,
        'ine_vs_selfie'       => 2.00,
        'sanciones'           => 0.80,
        'litigios'            => 1.50,
        'antecedentes_penales'=> 2.00,
        'selfie'              => 0.00,   // Sin costo extra (proceso interno)
        'presencia_en_linea'  => 0.00,   // Herramientas open source
        'horus_osint'         => 0.00,   // Herramientas open source
        'robin_darkweb'       => 0.50,   // Consumo LLM Anthropic
        'tlaloc_phone'        => 0.10,   // Tlaloc PNN
        'curp'                => 0.00,   // Gratuito vía NuFi
        'comprobante_domicilio' => 1.00,
        'nss_imss'            => 0.80,
        'cedula_profesional'  => 0.80,
        'score_crediticio'    => 2.50,
        'denue'               => 0.00,   // INEGI — API pública gratuita
    ],

    // Tarifas cobradas ficticiamente al tenant por consulta para margen/ingreso
    'prices' => [
        'rfc'                 => 1.50,
        'csd'                 => 3.00,
        'siger'               => 8.00,
        'sat_listas'          => 1.00,
        'marcas'              => 5.00,
        'ine_frente'          => 3.50,
        'ine_reverso'         => 3.50,
        'lista_nominal'       => 3.50,
        'ine_vs_selfie'       => 5.00,
        'sanciones'           => 2.00,
        'litigios'            => 4.00,
        'antecedentes_penales'=> 5.00,
        'selfie'              => 0.00,
        'presencia_en_linea'  => 0.00,
        'horus_osint'         => 0.00,
        'robin_darkweb'       => 2.00,
        'tlaloc_phone'        => 0.50,
        'curp'                => 0.00,
        'comprobante_domicilio' => 3.00,
        'nss_imss'            => 2.00,
        'cedula_profesional'  => 2.50,
        'score_crediticio'    => 6.00,
        'denue'               => 0.00,   // INEGI — sin margen, valor añadido gratuito
    ],

    /*
    |--------------------------------------------------------------------------
    | OSINT Service (Sherlock + Social Analyzer)
    |--------------------------------------------------------------------------
    | Microservicio Python en osint-service/app.py.
    | Con OSINT_SERVICE_ENABLED=false el conector usa mock data.
    |
    | Gobernanza: DECISIONS.md §10 — solo fuentes públicas, sin atributos
    | sensibles, con disclaimer obligatorio y revisión humana.
    */
    'osint' => [
        'enabled'       => env('OSINT_SERVICE_ENABLED', false),
        'horus_enabled' => env('OSINT_HORUS_ENABLED', false),
        'robin_enabled' => env('OSINT_ROBIN_ENABLED', false),
        'service_url'   => env('OSINT_SERVICE_URL', 'http://127.0.0.1:5001'),
        'secret'        => env('OSINT_SERVICE_SECRET', 'bkcheck-osint-dev-secret'),
        'robin'         => [
            'llm_provider' => 'anthropic',
            'model'        => env('ROBIN_ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | INEGI DENUE — Directorio Estadístico Nacional de Unidades Económicas
    |--------------------------------------------------------------------------
    | API pública gratuita del INEGI. Token requerido (registro gratuito).
    |
    | Registro: https://www.inegi.org.mx/app/desarrolladores/generatoken/
    | Con INEGI_DENUE_MOCK=true el conector usa datos simulados (sin token).
    */
    'denue' => [
        'token' => env('INEGI_DENUE_TOKEN', ''),
        'mock'  => env('INEGI_DENUE_MOCK', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tlaloc.sh — APIs de Validación y PNN (México)
    |--------------------------------------------------------------------------
    */
    'tlaloc' => [
        'base_url' => env('TLALOC_BASE_URL', 'https://api.tlaloc.sh/mx'),
        'api_key'  => env('TLALOC_API_KEY', ''),
        'mock'     => env('TLALOC_MOCK', false),
    ],
];


