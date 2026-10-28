<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PldScoringPolicy;
use App\Models\PldWebhookEndpoint;
use App\Models\Tenant;
use App\Services\Pld\PldCryptoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PldPolicyController extends Controller
{
    public function index()
    {
        $tenantId = (int) Auth::user()->tenant_id;
        PldScoringPolicy::seedDefaultsForTenant($tenantId);

        $tenant = Tenant::findOrFail($tenantId);
        if (empty($tenant->api_key)) {
            $tenant->api_key = 'avalid_live_' . bin2hex(random_bytes(24));
            $tenant->save();
        }

        $policies = PldScoringPolicy::where('tenant_id', $tenantId)
            ->get()
            ->keyBy('categoria');

        $webhooks = PldWebhookEndpoint::where('tenant_id', $tenantId)->get();

        return view('tenant.pld.policy', compact('tenant', 'policies', 'webhooks'));
    }

    public function update(Request $request)
    {
        $tenantId = (int) Auth::user()->tenant_id;

        $validated = $request->validate([
            'pesos'                             => 'required|array',
            'pesos.IDENTIDAD'                   => 'required|integer|min:0|max:100',
            'pesos.PLD'                         => 'required|integer|min:0|max:100',
            'pesos.CONSISTENCIA'                => 'required|integer|min:0|max:100',
            'pesos.REPUTACIONAL'                => 'required|integer|min:0|max:100',
            'intervalo_bajo_medio'              => 'required|integer|min:1|max:6',
            'intervalo_alto'                    => 'required|integer|min:1|max:6',
            'triggers'                          => 'nullable|array',
        ]);

        $sumaPesos = array_sum($validated['pesos']);
        if ($sumaPesos !== 100) {
            return back()->withErrors([
                'pesos' => "La suma de los pesos porcentuales de las 4 categorías debe ser exactamente 100% (actual: {$sumaPesos}%).",
            ])->withInput();
        }

        PldScoringPolicy::seedDefaultsForTenant($tenantId);

        foreach (['IDENTIDAD', 'PLD', 'CONSISTENCIA', 'REPUTACIONAL'] as $cat) {
            $policy = PldScoringPolicy::where('tenant_id', $tenantId)
                ->where('categoria', $cat)
                ->first();

            if (!$policy) {
                continue;
            }

            $triggersCfg = $policy->triggers_config ?? [];
            if (isset($validated['triggers'][$cat]) && is_array($validated['triggers'][$cat])) {
                foreach ($validated['triggers'][$cat] as $tKey => $tData) {
                    if (isset($triggersCfg[$tKey])) {
                        $triggersCfg[$tKey]['activo'] = !empty($tData['activo']);
                        if (isset($tData['valor']) && is_numeric($tData['valor'])) {
                            $triggersCfg[$tKey]['valor'] = max(0, min(100, (int) $tData['valor']));
                        }
                    }
                }
            }

            $policy->update([
                'peso_porcentual'                        => (int) $validated['pesos'][$cat],
                'triggers_config'                        => $triggersCfg,
                'intervalo_reevaluacion_bajo_medio_meses'=> (int) $validated['intervalo_bajo_medio'],
                'intervalo_reevaluacion_alto_meses'      => (int) $validated['intervalo_alto'],
            ]);
        }

        return redirect()->route('tenant.pld.policy')
            ->with('success', 'Política de puntuación y parámetros PLD/EBR actualizados para su organización.');
    }

    public function rotateApiKey()
    {
        $tenant = Tenant::findOrFail(Auth::user()->tenant_id);
        $tenant->api_key = 'avalid_live_' . bin2hex(random_bytes(24));
        $tenant->save();

        return redirect()->route('tenant.pld.policy')
            ->with('success', 'API Key de integración PLD regenerada exitosamente.');
    }
}
