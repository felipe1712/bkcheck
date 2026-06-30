<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:tenant_admin|investigador']);
    }

    /**
     * Display a listing of the subjects.
     */
    public function index()
    {
        $subjects = Subject::with('project')->paginate(10);
        return view('tenant.subjects.index', compact('subjects'));
    }

    /**
     * Show the form for creating a new subject.
     */
    public function create(Request $request)
    {
        // Get all projects for selection (automatically isolated)
        $projects = Project::all();
        $selectedProjectId = $request->query('project_id');

        return view('tenant.subjects.create', compact('projects', 'selectedProjectId'));
    }

    /**
     * Store a newly created subject in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'tipo' => 'required|string|in:persona_fisica,persona_moral',
            'name_or_company' => 'required|string|max:255',
            'rfc' => [
                'required',
                'string',
                'min:12',
                'max:13',
                // Custom regex to validate Mexican RFC structure:
                // Moral (12 chars): 3 letters, 6 digits, 3 alphanumeric homoclave
                // Fisica (13 chars): 4 letters, 6 digits, 3 alphanumeric homoclave
                'regex:/^([A-ZÑ&]{3,4}) ?(?:- ?)?(\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])) ?(?:- ?)?([A-Z\d]{2}[A\d])$/i'
            ],
            'curp' => [
                'nullable',
                'required_if:tipo,persona_fisica',
                'string',
                'size:18',
                'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]\d$/i'
            ],
            'address' => 'nullable|string|max:500',
            'consent_granted' => 'required|boolean|accepted',
            'consent_legal_basis' => 'required|string|max:255',
            'consent_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
        ]);

        $consentPath = null;
        if ($request->hasFile('consent_document')) {
            // Store the file securely in private local storage
            $consentPath = $request->file('consent_document')->store('consent_documents');
        }

        $subject = Subject::create([
            'project_id' => $request->project_id,
            'tipo' => $request->tipo,
            'name_or_company' => $request->name_or_company,
            'rfc' => strtoupper($request->rfc),
            'curp' => $request->curp ? strtoupper($request->curp) : null,
            'address' => $request->address,
            'consent_granted' => $request->consent_granted,
            'consent_date' => now(),
            'consent_legal_basis' => $request->consent_legal_basis,
            'consent_document_path' => $consentPath,
        ]);

        // Log activity
        activity()
            ->performedOn($subject)
            ->causedBy(auth()->user())
            ->log("Sujeto de investigación registrado: {$subject->name_or_company} (RFC: {$request->rfc})");

        return redirect()->route('tenant.projects.show', $request->project_id)
            ->with('success', 'Sujeto registrado correctamente.');
    }

    /**
     * Display the specified subject and its investigation dashboard.
     */
    public function show($id)
    {
        $subject = Subject::findOrFail($id);
        
        // Retrieve associated source queries and their results
        $queries = \Illuminate\Support\Facades\Schema::hasTable('source_queries') 
            ? \App\Models\SourceQuery::where('subject_id', $subject->id)->with('result')->get()
            : collect();

        return view('tenant.subjects.show', compact('subject', 'queries'));
    }

    /**
     * Show the form for editing the specified subject.
     */
    public function edit($id)
    {
        $subject = Subject::findOrFail($id);
        $projects = Project::all();

        return view('tenant.subjects.edit', compact('subject', 'projects'));
    }

    /**
     * Update the specified subject in storage.
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'tipo' => 'required|string|in:persona_fisica,persona_moral',
            'name_or_company' => 'required|string|max:255',
            'rfc' => [
                'required',
                'string',
                'min:12',
                'max:13',
                'regex:/^([A-ZÑ&]{3,4}) ?(?:- ?)?(\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])) ?(?:- ?)?([A-Z\d]{2}[A\d])$/i'
            ],
            'curp' => [
                'nullable',
                'required_if:tipo,persona_fisica',
                'string',
                'size:18',
                'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]\d$/i'
            ],
            'address' => 'nullable|string|max:500',
            'consent_legal_basis' => 'required|string|max:255',
            'consent_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $consentPath = $subject->consent_document_path;
        if ($request->hasFile('consent_document')) {
            // Delete old file if exists
            if ($consentPath) {
                Storage::delete($consentPath);
            }
            $consentPath = $request->file('consent_document')->store('consent_documents');
        }

        $subject->update([
            'project_id' => $request->project_id,
            'tipo' => $request->tipo,
            'name_or_company' => $request->name_or_company,
            'rfc' => strtoupper($request->rfc),
            'curp' => $request->curp ? strtoupper($request->curp) : null,
            'address' => $request->address,
            'consent_legal_basis' => $request->consent_legal_basis,
            'consent_document_path' => $consentPath,
        ]);

        // Log activity
        activity()
            ->performedOn($subject)
            ->causedBy(auth()->user())
            ->log("Sujeto de investigación actualizado: {$subject->name_or_company}");

        return redirect()->route('tenant.projects.show', $request->project_id)
            ->with('success', 'Sujeto actualizado correctamente.');
    }

    /**
     * Remove the specified subject from storage.
     */
    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $projectId = $subject->project_id;

        // Delete consent file if exists
        if ($subject->consent_document_path) {
            Storage::delete($subject->consent_document_path);
        }

        // Log activity before deletion
        activity()
            ->performedOn($subject)
            ->causedBy(auth()->user())
            ->log("Sujeto de investigación eliminado: {$subject->name_or_company}");

        $subject->delete();

        return redirect()->route('tenant.projects.show', $projectId)
            ->with('success', 'Sujeto eliminado correctamente.');
    }
}
