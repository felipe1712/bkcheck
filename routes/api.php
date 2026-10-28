<?php

use App\Http\Controllers\Api\PldModuleApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/nufi/webhook', [App\Http\Controllers\NufiWebhookController::class, 'handle']);

// ─────────────────────────────────────────────────────────────────────────
// Módulo de Expedientes PLD/EBR, Reconsulta, Versionado y Webhooks (M10)
// Autenticado mediante API Key de Tenant (X-Tenant-Api-Key o Bearer) o sesión activa
// ─────────────────────────────────────────────────────────────────────────
Route::middleware(['tenant.apikey'])->group(function () {
    Route::post('/personas/{id}/monitoreo',   [PldModuleApiController::class, 'subscribeMonitoring']);
    Route::delete('/personas/{id}/monitoreo', [PldModuleApiController::class, 'cancelMonitoring']);
    Route::post('/personas/{id}/expedientes', [PldModuleApiController::class, 'generateExpediente']);
    Route::get('/personas/{id}/expedientes',  [PldModuleApiController::class, 'listExpedientes']);
    Route::get('/expedientes/{folio}',        [PldModuleApiController::class, 'showExpediente']);
    Route::get('/expedientes/{folio}/diff',   [PldModuleApiController::class, 'diffExpediente']);
    Route::post('/expedientes/verificar',     [PldModuleApiController::class, 'verifyExpediente']);
    Route::post('/webhooks',                  [PldModuleApiController::class, 'configureWebhook']);
    Route::get('/eventos',                    [PldModuleApiController::class, 'listEventos']);
});
