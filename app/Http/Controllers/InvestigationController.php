<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\BackgroundCheck\InvestigationRunner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvestigationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:tenant_admin|investigador']);
    }

    /**
     * Start background check investigation for the given subject.
     */
    public function investigate($id)
    {
        try {
            $subject = Subject::findOrFail($id);

            // Execute investigation runner (which validates limits and dispatches jobs)
            $runner = new InvestigationRunner();
            $runner->run($subject);

            // Log activity
            activity()
                ->performedOn($subject)
                ->causedBy(auth()->user())
                ->log("Investigación iniciada para el sujeto: {$subject->name_or_company}");

            return redirect()->route('tenant.subjects.show', $subject->id)
                ->with('success', 'Investigación iniciada correctamente en segundo plano. Los resultados se actualizarán a continuación.');

        } catch (\Throwable $e) {
            Log::error("Error al iniciar investigación para el sujeto ID {$id}: " . $e->getMessage());
            
            return redirect()->route('tenant.subjects.show', $id)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Start single background check query for the given subject and source type.
     */
    public function investigateSource($id, $sourceType)
    {
        try {
            $subject = Subject::findOrFail($id);

            // Execute investigation runner for a single source
            $runner = new InvestigationRunner();
            $runner->runSingle($subject, $sourceType);

            // Log activity
            activity()
                ->performedOn($subject)
                ->causedBy(auth()->user())
                ->log("Consulta individual de {$sourceType} iniciada para el sujeto: {$subject->name_or_company}");

            return redirect()->route('tenant.subjects.show', $subject->id)
                ->with('success', 'Consulta individual iniciada correctamente en segundo plano. Los resultados se actualizarán a continuación.');

        } catch (\Throwable $e) {
            Log::error("Error al iniciar consulta {$sourceType} para el sujeto ID {$id}: " . $e->getMessage());
            
            return redirect()->route('tenant.subjects.show', $id)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
