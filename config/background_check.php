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

    // Estimación de costos internos en MXN por API (Tlaloc-primero / NuFi fallback)
    'costs_tlaloc' => [
        'rfc'          => 0.10,
        'curp'         => 0.25,
        'tlaloc_phone' => 0.10,
    ],
    'prices_tlaloc' => [
        'rfc'          => 0.50,
        'curp'         => 0.75,
        'tlaloc_phone' => 0.50,
    ],

    'costs' => [
        'rfc'                 => 3.00,   // NuFi fallback (Tlaloc: $0.10 MXN)
        'csd'                 => 3.00,
        'siger'               => 3.00,
        'sat_listas'          => 3.00,
        'marcas'              => 3.00,
        'ine_frente'          => 3.00,
        'ine_reverso'         => 3.00,
        'lista_nominal'       => 3.00,
        'ine_vs_selfie'       => 3.00,
        'sanciones'           => 3.00,
        'litigios'            => 3.00,
        'antecedentes_penales'=> 3.00,
        'selfie'              => 0.00,
        'presencia_en_linea'  => 0.00,   // Herramientas open source
        'horus_osint'         => 0.00,   // Herramientas open source
        'robin_darkweb'       => 1.50,   // Consumo LLM Anthropic
        'tlaloc_phone'        => 0.10,   // Tlaloc PNN
        'curp'                => 3.00,   // NuFi fallback (Tlaloc: $0.25 MXN)
        'comprobante_domicilio' => 3.00,
        'nss_imss'            => 3.00,
        'cedula_profesional'  => 3.00,
        'score_crediticio'    => 5.00,
        'denue'               => 0.00,   // INEGI — API pública gratuita
    ],

    // Tarifas al tenant en MXN por consulta
    'prices' => [
        'rfc'                 => 5.00,   // NuFi fallback (Tlaloc: $0.50 MXN)
        'csd'                 => 8.00,
        'siger'               => 12.00,
        'sat_listas'          => 5.00,
        'marcas'              => 10.00,
        'ine_frente'          => 6.00,
        'ine_reverso'         => 6.00,
        'lista_nominal'       => 6.00,
        'ine_vs_selfie'       => 8.00,
        'sanciones'           => 5.00,
        'litigios'            => 8.00,
        'antecedentes_penales'=> 8.00,
        'selfie'              => 0.00,
        'presencia_en_linea'  => 0.00,
        'horus_osint'         => 0.00,
        'robin_darkweb'       => 5.00,
        'tlaloc_phone'        => 0.50,
        'curp'                => 5.00,   // NuFi fallback (Tlaloc: $0.75 MXN)
        'comprobante_domicilio' => 6.00,
        'nss_imss'            => 6.00,
        'cedula_profesional'  => 6.00,
        'score_crediticio'    => 15.00,
        'denue'               => 0.00,
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


