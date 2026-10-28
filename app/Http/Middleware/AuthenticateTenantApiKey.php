<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateTenantApiKey
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Si hay sesión autenticada con tenant_id, permitir
        if (Auth::check() && Auth::user()->tenant_id) {
            $tenant = Tenant::find(Auth::user()->tenant_id);
            if ($tenant && $tenant->activo) {
                $request->attributes->set('tenant', $tenant);
                return $next($request);
            }
        }

        // 2. Buscar API Key en X-Tenant-Api-Key o Authorization: Bearer
        $apiKey = $request->header('X-Tenant-Api-Key') ?: $request->bearerToken();

        if (!$apiKey) {
            return response()->json([
                'error'   => 'No autorizado',
                'message' => 'Se requiere encabezado X-Tenant-Api-Key o Authorization: Bearer válido.',
            ], 401);
        }

        $tenant = Tenant::where('api_key', $apiKey)->where('activo', true)->first();

        if (!$tenant) {
            return response()->json([
                'error'   => 'No autorizado',
                'message' => 'API Key de tenant inválida o cuenta inactiva.',
            ], 401);
        }

        $request->attributes->set('tenant', $tenant);
        return $next($request);
    }
}
