<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

// Super Admin Panel Routes
Route::middleware(['auth', 'role:super_admin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/activity-logs', [App\Http\Controllers\SuperAdminController::class, 'activityLogs'])->name('activity-logs');
    Route::get('/audit-logs', [App\Http\Controllers\SuperAdminController::class, 'auditLogs'])->name('audit-logs');
    Route::get('/api-logs', [App\Http\Controllers\SuperAdminController::class, 'apiLogs'])->name('api-logs');
    Route::resource('tenants', App\Http\Controllers\SuperAdminController::class);
    Route::patch('users/{id}/toggle-status', [App\Http\Controllers\SuperAdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::resource('users', App\Http\Controllers\SuperAdminUserController::class);
});

//Language Translation
Route::get('index/{locale}', [App\Http\Controllers\HomeController::class, 'lang']);

// Tenant Panel Routes (accessible to tenant_admin and investigador)
Route::middleware(['auth', 'role:tenant_admin|investigador'])->prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\TenantDashboardController::class, 'index'])->name('dashboard');
    Route::resource('projects', App\Http\Controllers\ProjectController::class);
    
    // Background check execution and report endpoints
    Route::post('/subjects/{id}/investigate', [App\Http\Controllers\InvestigationController::class, 'investigate'])->name('subjects.investigate');
    Route::post('/subjects/{id}/investigate/{source_type}', [App\Http\Controllers\InvestigationController::class, 'investigateSource'])->name('subjects.investigate.source');
    Route::get('/subjects/{id}/report', [App\Http\Controllers\ReportController::class, 'downloadReport'])->name('subjects.report');
    
    Route::post('/subjects/{id}/tier', [App\Http\Controllers\SubjectController::class, 'updateTierLevel'])->name('subjects.update-tier');
    Route::resource('subjects', App\Http\Controllers\SubjectController::class);

    // Tenant User Management & Consumption (exclusively for tenant_admin)
    Route::middleware(['role:tenant_admin'])->group(function () {
        Route::resource('users', App\Http\Controllers\TenantUserController::class);
        Route::get('/consumption', [App\Http\Controllers\TenantDashboardController::class, 'consumption'])->name('consumption');
        // Configuración del tenant: Términos y Condiciones de enrolamiento
        Route::get('/settings',  [App\Http\Controllers\TenantSettingsController::class, 'index']) ->name('settings');
        Route::post('/settings', [App\Http\Controllers\TenantSettingsController::class, 'update'])->name('settings.update');
    });

    // Regenerar token de enrolamiento (tenant_admin e investigador)
    Route::post('/subjects/{id}/regenerate-enrollment',
        [App\Http\Controllers\SubjectController::class, 'regenerateEnrollment']
    )->name('subjects.regenerate-enrollment');

    // Servir documentos privados del sujeto (INE, selfie, consentimiento, comprobante)
    Route::get('/subjects/{id}/document/{type}',
        [App\Http\Controllers\SubjectController::class, 'serveDocument']
    )->name('subjects.document');

    // Subida manual de documentos desde el panel (reemplaza el archivo existente)
    Route::post('/subjects/{id}/document/{type}',
        [App\Http\Controllers\SubjectController::class, 'uploadDocument']
    )->name('subjects.document.upload');

    // Borrado de documentos (resetea enrolamiento si se borran los 3 principales)
    Route::delete('/subjects/{id}/document/{type}',
        [App\Http\Controllers\SubjectController::class, 'deleteDocument']
    )->name('subjects.document.delete');
});

Route::get('/', [App\Http\Controllers\HomeController::class, 'root'])->name('root');

//Update User Details
Route::post('/update-profile/{id}', [App\Http\Controllers\HomeController::class, 'updateProfile'])->name('updateProfile');
Route::post('/update-password/{id}', [App\Http\Controllers\HomeController::class, 'updatePassword'])->name('updatePassword');

// ─────────────────────────────────────────────────────────
// RUTAS PÚBLICAS — Enrolamiento del Investigado (sin auth)
// Seguridad basada en token UUID + expiración 24h
// ─────────────────────────────────────────────────────────
// Ruta ultra-corta de enrolamiento: /e/{token}
Route::get('/e/{token}', [App\Http\Controllers\EnrollmentController::class, 'show'])->middleware('throttle:10,1')->name('enroll.short');

Route::prefix('enroll')->name('enroll.')->middleware('throttle:10,1')->group(function () {
    Route::get('/{token}',             [App\Http\Controllers\EnrollmentController::class, 'show'])        ->name('show');
    Route::post('/{token}/accept-tc',  [App\Http\Controllers\EnrollmentController::class, 'acceptTerms']) ->name('accept-tc');
    Route::post('/{token}/upload',     [App\Http\Controllers\EnrollmentController::class, 'upload'])      ->name('upload');
    Route::post('/{token}/liveness-session', [App\Http\Controllers\EnrollmentController::class, 'startLivenessSession'])->name('liveness-session');
    Route::get('/{token}/liveness-mock',     [App\Http\Controllers\EnrollmentController::class, 'livenessMock'])         ->name('liveness-mock');
    Route::get('/{token}/liveness-done',     [App\Http\Controllers\EnrollmentController::class, 'livenessDone'])         ->name('liveness-done');
    Route::get('/{token}/done',              [App\Http\Controllers\EnrollmentController::class, 'done'])                 ->name('done');
});

Route::get('{any}', [App\Http\Controllers\HomeController::class, 'index'])->name('index');
