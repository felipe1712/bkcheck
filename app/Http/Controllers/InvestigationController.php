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
    public function investigate(Request $request, $id)
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

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Investigación iniciada correctamente en segundo plano.',
                ]);
            }

            return redirect()->route('tenant.subjects.show', $subject->id)
                ->with('success', 'Investigación iniciada correctamente en segundo plano. Los resultados se actualizarán a continuación.');

        } catch (\Throwable $e) {
            Log::error("Error al iniciar investigación para el sujeto ID {$id}: " . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('tenant.subjects.show', $id)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Start single background check query for the given subject and source type.
     */
    public function investigateSource(Request $request, $id, $sourceType)
    {
        try {
            $subject = Subject::findOrFail($id);

            // Handle uploading new file if provided
            if ($sourceType === 'ine_frente' && $request->hasFile('ine_front')) {
                $request->validate([
                    'ine_front' => 'required|image|mimes:jpg,jpeg,png|max:5120',
                ]);
                if ($subject->ine_front_path) {
                    \Illuminate\Support\Facades\Storage::delete($subject->ine_front_path);
                }
                $subject->ine_front_path = $request->file('ine_front')->store('ine_documents');
                $subject->save();
            } elseif ($sourceType === 'ine_reverso' && $request->hasFile('ine_back')) {
                $request->validate([
                    'ine_back' => 'required|image|mimes:jpg,jpeg,png|max:5120',
                ]);
                if ($subject->ine_back_path) {
                    \Illuminate\Support\Facades\Storage::delete($subject->ine_back_path);
                }
                $subject->ine_back_path = $request->file('ine_back')->store('ine_documents');
                $subject->save();
            }

            // Execute investigation runner for a single source
            $runner = new InvestigationRunner();
            $runner->runSingle($subject, $sourceType);

            // Log activity
            activity()
                ->performedOn($subject)
                ->causedBy(auth()->user())
                ->log("Consulta individual de {$sourceType} iniciada para el sujeto: {$subject->name_or_company}");

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Consulta individual iniciada correctamente en segundo plano.',
                ]);
            }

            return redirect()->route('tenant.subjects.show', $subject->id)
                ->with('success', 'Consulta individual iniciada correctamente en segundo plano. Los resultados se actualizarán a continuación.');

        } catch (\Throwable $e) {
            Log::error("Error al iniciar consulta {$sourceType} para el sujeto ID {$id}: " . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('tenant.subjects.show', $id)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
