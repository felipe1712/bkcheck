<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SourceQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:tenant_admin|investigador']);
    }

    /**
     * Generate and download the consolidated PDF report for the given subject.
     */
    public function downloadReport($id)
    {
        try {
            $subject = Subject::findOrFail($id);

            // Fetch all successfully completed queries with their results
            $queries = SourceQuery::where('subject_id', $subject->id)
                ->where('status', 'completed')
                ->with('result')
                ->get();

            if ($queries->isEmpty()) {
                return redirect()->route('tenant.subjects.show', $subject->id)
                    ->withErrors(['error' => 'No se puede generar el reporte porque aún no hay consultas completadas para este sujeto.']);
            }

            // Register audit trail for report export
            activity()
                ->performedOn($subject)
                ->causedBy(auth()->user())
                ->log("Reporte PDF exportado para el sujeto: {$subject->name_or_company}");

            // Render PDF using Laravel DomPDF
            $pdf = Pdf::loadView('tenant.reports.subject_pdf', compact('subject', 'queries'));

            // Use stream to open in browser window/tab
            return $pdf->stream("Expediente_{$subject->rfc}_{$subject->id}.pdf");

        } catch (\Throwable $e) {
            Log::error("Error al generar reporte PDF para sujeto ID {$id}: " . $e->getMessage());
            
            return redirect()->route('tenant.subjects.show', $id)
                ->withErrors(['error' => 'Error al generar el reporte PDF: ' . $e->getMessage()]);
        }
    }
}
