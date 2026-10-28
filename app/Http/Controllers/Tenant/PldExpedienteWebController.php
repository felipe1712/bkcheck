<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PldExpedienteVersion;
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

    public function generate(Request $request, int $subjectId)
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $subject = Subject::where('id', $subjectId)->firstOrFail();

        $tipo = $request->input('tipo_ejecucion', 'BAJO_DEMANDA');
        $version = $this->versioning->generateVersion($subject, $tipo);

        return redirect()
            ->route('tenant.subjects.show', $subject->id)
            ->with('success', "Expediente PLD/EBR generado exitosamente con folio {$version->folio} (SHA-256: " . substr($version->hash_version, 0, 16) . "...).");
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
