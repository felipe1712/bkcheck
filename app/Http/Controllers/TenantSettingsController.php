<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TenantSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:tenant_admin']);
    }

    /**
     * Muestra el formulario de configuración del tenant (T&C de enrolamiento).
     */
    public function index()
    {
        $tenant = auth()->user()->tenant;
        return view('tenant.settings', compact('tenant'));
    }

    /**
     * Guarda los Términos y Condiciones de enrolamiento del tenant.
     */
    public function update(Request $request)
    {
        $request->validate([
            'enrollment_terms' => 'nullable|string|max:20000',
        ]);

        $tenant = auth()->user()->tenant;
        $tenant->update([
            'enrollment_terms'            => $request->enrollment_terms,
            'enrollment_terms_updated_at' => now(),
        ]);

        activity()
            ->performedOn($tenant)
            ->causedBy(auth()->user())
            ->log('Términos y Condiciones de enrolamiento actualizados.');

        return redirect()->route('tenant.settings')
            ->with('success', 'Términos y Condiciones guardados correctamente.');
    }
}
