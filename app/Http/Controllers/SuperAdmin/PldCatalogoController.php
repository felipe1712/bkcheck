<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ExpedientePldSujeto;
use App\Models\PldEvento;
use App\Models\PldExpedienteVersion;
use App\Models\PldFuenteCatalogo;
use App\Models\PldSuscripcion;
use App\Models\Tenant;
use Illuminate\Http\Request;

class PldCatalogoController extends Controller
{
    public function index()
    {
        PldFuenteCatalogo::ensureDefaultsSeeded();
        $fuentes = PldFuenteCatalogo::orderBy('categoria')->orderBy('fuente_key')->get();

        $stats = [
            'total_expedientes'   => ExpedientePldSujeto::withoutGlobalScopes()->count(),
            'total_versiones'     => PldExpedienteVersion::withoutGlobalScopes()->count(),
            'monitoreos_activos'  => PldSuscripcion::where('estado', 'ACTIVA')->count(),
            'eventos_emitidos'    => PldEvento::count(),
            'eventos_pendientes'  => PldEvento::whereNull('entregado_en')->count(),
        ];

        $tenantsSummary = Tenant::withCount([
            'pldScoringPolicies',
        ])->get()->map(function (Tenant $t) {
            $exps = ExpedientePldSujeto::withoutGlobalScopes()->where('tenant_id', $t->id)->get();
            return [
                'tenant'             => $t,
                'total_expedientes'  => $exps->count(),
                'alto'               => $exps->where('nivel_riesgo_persona', 'ALTO')->count(),
                'medio'              => $exps->where('nivel_riesgo_persona', 'MEDIO')->count(),
                'bajo'               => $exps->where('nivel_riesgo_persona', 'BAJO')->count(),
                'monitoreos_activos' => PldSuscripcion::where('tenant_id', $t->id)->where('estado', 'ACTIVA')->count(),
            ];
        });

        return view('superadmin.pld-catalogo.index', compact('fuentes', 'stats', 'tenantsSummary'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fuente_key' => 'required|string|max:64|unique:pld_fuente_catalogo,fuente_key',
            'nombre'     => 'required|string|max:190',
            'categoria'  => 'required|string|in:IDENTIDAD,PLD,CONSISTENCIA,REPUTACIONAL',
            'criticidad' => 'required|string|in:ALTA,MEDIA,BAJA',
            'activo'     => 'nullable|boolean',
        ]);

        PldFuenteCatalogo::create([
            'fuente_key' => strtolower(trim($validated['fuente_key'])),
            'nombre'     => $validated['nombre'],
            'categoria'  => $validated['categoria'],
            'criticidad' => $validated['criticidad'],
            'activo'     => $request->boolean('activo', true),
        ]);

        return redirect()->route('superadmin.pld-catalogo.index')
            ->with('success', 'Fuente agregada al catálogo global PLD.');
    }

    public function update(Request $request, int $id)
    {
        $fuente = PldFuenteCatalogo::findOrFail($id);

        $validated = $request->validate([
            'nombre'     => 'required|string|max:190',
            'categoria'  => 'required|string|in:IDENTIDAD,PLD,CONSISTENCIA,REPUTACIONAL',
            'criticidad' => 'required|string|in:ALTA,MEDIA,BAJA',
            'activo'     => 'nullable|boolean',
        ]);

        $fuente->update([
            'nombre'     => $validated['nombre'],
            'categoria'  => $validated['categoria'],
            'criticidad' => $validated['criticidad'],
            'activo'     => $request->boolean('activo', false),
        ]);

        return redirect()->route('superadmin.pld-catalogo.index')
            ->with('success', "Fuente '{$fuente->fuente_key}' actualizada.");
    }
}
