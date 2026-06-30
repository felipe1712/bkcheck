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
        'mock' => env('NUFI_MOCK', true), // Mock by default for safety in sandbox
        'webhook_url' => env('NUFI_WEBHOOK_URL'),
    ],

    // Estimación de costos ficticios internos por API para tableros analíticos
    'costs' => [
        'rfc' => 0.50,         // USD
        'csd' => 1.20,         // USD
        'siger' => 3.50,       // USD
        'sat_listas' => 0.40,  // USD
        'marcas' => 2.00,      // USD
    ],

    // Tarifas cobradas ficticiamente al tenant por consulta para margen/ingreso
    'prices' => [
        'rfc' => 1.50,
        'csd' => 3.00,
        'siger' => 8.00,
        'sat_listas' => 1.00,
        'marcas' => 5.00,
    ],
];
