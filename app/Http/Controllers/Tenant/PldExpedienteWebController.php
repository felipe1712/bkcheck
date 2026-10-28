<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ExpedientePldSujeto;
use App\Models\PldExpedienteVersion;
use App\Models\PldSuscripcion;
use App\Models\Subject;
use App\Services\Pld\PLDVersioningService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PldExpedienteWebController extends Controller
{
    public function __construct(
        protected PLDVersioningService $versioning
    ) {}

    /**
     * Tablero de Expedientes PLD/EBR y Monitoreo Periódico del Cliente.
     */
    public function index()
    {
        $tenantId = (int) Auth::user()->tenant_id;

        $subjects = Subject::with(['project', 'expedientePld', 'pldSuscripcion', 'pldExpedienteVersiones'])
            ->orderByDesc('updated_at')
            ->get();

        $expedientes = ExpedientePldSujeto::where('tenant_id', $tenantId)->get();
        $stats = [
            'total_sujetos'      => $subjects->count(),
            'total_expedientes'  => $expedientes->count(),
            'alto'               => $expedientes->where('nivel_riesgo_persona', 'ALTO')->count(),
            'medio'              => $expedientes->where('nivel_riesgo_persona', 'MEDIO')->count(),
            'bajo'               => $expedientes->where('nivel_riesgo_persona', 'BAJO')->count(),
            'monitoreos_activos' => PldSuscripcion::where('tenant_id', $tenantId)->where('estado', 'ACTIVA')->count(),
        ];

        return view('tenant.pld.index', compact('subjects', 'stats'));
    }

    /**
     * Visor de Expediente PLD/EBR, Historial de Versiones Inmutables (JCS) y Comparador Diff.
     */
    public function show(Request $request, int $subjectId)
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $subject = Subject::with(['project', 'expedientePld', 'pldSuscripcion'])
            ->where('id', $subjectId)
            ->firstOrFail();

        $versions = PldExpedienteVersion::where('tenant_id', $tenantId)
            ->where('subject_id', $subject->id)
            ->orderByDesc('version')
            ->get();

        $selectedFolio = $request->query('folio') ?: $versions->first()?->folio;
        $activeVersion = $selectedFolio
            ? $versions->firstWhere('folio', $selectedFolio) ?? $versions->first()
            : null;

        $exp = $activeVersion ? json_decode($activeVersion->payload_canonico, true) : null;

        $contraFolio = $request->query('contra');
        $diff = null;
        if ($activeVersion && $versions->count() > 1) {
            try {
                $diff = $this->versioning->diffBetweenFolios($activeVersion->folio, $contraFolio ?: null);
            } catch (\Throwable) {
                $diff = $activeVersion->cambios_resumen;
            }
        }

        return view('tenant.pld.show', compact(
            'subject',
            'versions',
            'activeVersion',
            'exp',
            'diff',
            'contraFolio'
        ));
    }

    public function generate(Request $request, int $subjectId)
    {
        $subject = Subject::where('id', $subjectId)->firstOrFail();
        $tipo = $request->input('tipo_ejecucion', 'BAJO_DEMANDA');

        try {
            $version = $this->versioning->generateVersion($subject, $tipo);
        } catch (\DomainException $e) {
            return back()->withErrors(['pld' => $e->getMessage()]);
        }

        $redirectTo = $request->input('redirect_to', 'pld_show');
        if ($redirectTo === 'subject_show') {
            return redirect()
                ->route('tenant.subjects.show', $subject->id)
                ->with('success', "Expediente PLD/EBR generado exitosamente con folio {$version->folio} (SHA-256: " . substr($version->hash_version, 0, 16) . "...).");
        }

        return redirect()
            ->route('tenant.pld.expedientes.show', ['id' => $subject->id, 'folio' => $version->folio])
            ->with('success', "Versión inmutable {$version->folio} generada y firmada con JCS (SHA-256: " . substr($version->hash_version, 0, 16) . "...).");
    }

    public function toggleMonitoring(Request $request, int $subjectId)
    {
        $subject = Subject::where('id', $subjectId)->firstOrFail();
        $accion = $request->input('accion', 'activar');

        if ($accion === 'baja') {
            $motivo = $request->input('motivo', 'relacion_terminada');
            $this->versioning->cancelSubscription($subject, $motivo);
            return back()->with('success', "Monitoreo periódico dado de BAJA ({$motivo}). Se detuvieron las reconsultas futuras.");
        }

        $sub = $this->versioning->createOrUpdateSubscription($subject, [
            'finalidad'          => $request->input('finalidad', $subject->finalidad_clave ?: 'alta_cliente'),
            'consentimiento_ref' => $request->input('consentimiento_ref', 'CONS-PANEL-' . now()->format('Ymd')),
            'intervalo_meses'    => (int) $request->input('intervalo_meses', 6),
        ]);

        return back()->with('success', "Monitoreo periódico ACTIVO (próxima reevaluación: " . $sub->proxima_reevaluacion?->format('d/m/Y') . ").");
    }

    public function downloadPdf(string $folio)
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $version = PldExpedienteVersion::where('tenant_id', $tenantId)
            ->where('folio', $folio)
            ->firstOrFail();

        $exp = json_decode($version->payload_canonico, true);

        $pdf = Pdf::loadView('tenant.reports.pld_expediente_pdf', [
            'exp'           => $exp,
            'formattedText' => $version->expediente_formateado_texto,
        ])->setPaper('letter', 'portrait');

        return $pdf->download("Expediente_PLD_{$folio}.pdf");
    }

    public function downloadJson(string $folio)
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $version = PldExpedienteVersion::where('tenant_id', $tenantId)
            ->where('folio', $folio)
            ->firstOrFail();

        return response($version->payload_canonico, 200, [
            'Content-Type'        => 'application/json; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"Expediente_PLD_{$folio}.json\"",
        ]);
    }
}
