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
            'project_id'           => 'required|exists:projects,id',
            'tipo'                 => 'required|string|in:persona_fisica,persona_moral',
            'name_or_company'      => 'required|string|max:255',
            'rfc' => [
                'required', 'string', 'min:12', 'max:13',
                'regex:/^([A-ZÑ&]{3,4}) ?(?:- ?)?(\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])) ?(?:- ?)?([A-Z\d]{2}[A\d])$/i'
            ],
            'curp' => [
                'nullable', 'required_if:tipo,persona_fisica', 'string', 'size:18',
                'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]\d$/i'
            ],
            'address'              => 'nullable|string|max:500',
            'consent_granted'      => 'required|boolean|accepted',
            'consent_legal_basis'  => 'required|string|max:255',
            'consent_document'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'ine_front'            => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'ine_back'             => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            // Tier 2
            'username'             => 'nullable|string|max:100',
            'nss'                  => 'nullable|digits:11',
            'proof_of_address'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'credit_consent_granted' => 'nullable|boolean',
        ]);

        $consentPath = null;
        if ($request->hasFile('consent_document')) {
            $consentPath = $request->file('consent_document')->store('consent_documents');
        }

        $ineFrontPath = null;
        if ($request->hasFile('ine_front')) {
            $ineFrontPath = $request->file('ine_front')->store('ine_documents');
        }

        $ineBackPath = null;
        if ($request->hasFile('ine_back')) {
            $ineBackPath = $request->file('ine_back')->store('ine_documents');
        }

        // Tier 2: Comprobante de domicilio
        $proofOfAddressPath = null;
        if ($request->hasFile('proof_of_address')) {
            $proofOfAddressPath = $request->file('proof_of_address')->store('proof_of_address_documents');
        }

        $creditConsent = (bool) $request->input('credit_consent_granted', false);

        $subject = Subject::create([
            'project_id'             => $request->project_id,
            'tipo'                   => $request->tipo,
            'name_or_company'        => $request->name_or_company,
            'rfc'                    => strtoupper($request->rfc),
            'curp'                   => $request->curp ? strtoupper($request->curp) : null,
            'address'                => $request->address,
            'consent_granted'        => $request->consent_granted,
            'consent_date'           => now(),
            'consent_legal_basis'    => $request->consent_legal_basis,
            'consent_document_path'  => $consentPath,
            'ine_front_path'         => $ineFrontPath,
            'ine_back_path'          => $ineBackPath,
            // Tier 2
            'username'               => $request->username ?: null,
            'nss'                    => $request->nss ?: null,
            'proof_of_address_path'  => $proofOfAddressPath,
            'credit_consent_granted' => $creditConsent,
            'credit_consent_at'      => $creditConsent ? now() : null,
        ]);

        // Generar token de enrolamiento automáticamente (válido 24h)
        $subject->generateEnrollmentToken();

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
     * Sirve de forma segura un documento privado del sujeto.
     * Solo accesible para usuarios autenticados del mismo tenant.
     */
    public function serveDocument($id, string $type)
    {
        $subject = Subject::findOrFail($id);

        $pathMap = [
            'ine_front'        => $subject->ine_front_path,
            'ine_back'         => $subject->ine_back_path,
            'selfie'           => $subject->selfie_path,
            'consent'          => $subject->consent_document_path,
            'proof_of_address' => $subject->proof_of_address_path,
        ];

        $path = $pathMap[$type] ?? null;

        if (!$path || !Storage::exists($path)) {
            abort(404, 'Documento no encontrado.');
        }

        $mime = Storage::mimeType($path) ?: 'application/octet-stream';

        return response()->stream(function () use ($path) {
            echo Storage::get($path);
        }, 200, [
            'Content-Type'           => $mime,
            'Content-Disposition'    => 'inline',
            'Cache-Control'          => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Sube manualmente un documento del sujeto desde el panel de administración.
     * POST /tenant/subjects/{id}/document/{type}
     */
    public function uploadDocument($id, string $type, Request $request)
    {
        $subject = Subject::findOrFail($id);

        $fieldMap = [
            'ine_front'        => 'ine_front_path',
            'ine_back'         => 'ine_back_path',
            'selfie'           => 'selfie_path',
            'consent'          => 'consent_document_path',
            'proof_of_address' => 'proof_of_address_path',
        ];

        $dirMap = [
            'ine_front'        => 'ine_documents',
            'ine_back'         => 'ine_documents',
            'selfie'           => 'enrollment_selfies',
            'consent'          => 'consent_documents',
            'proof_of_address' => 'proof_of_address_documents',
        ];

        if (!isset($fieldMap[$type])) {
            return back()->with('error', 'Tipo de documento no válido.');
        }

        $request->validate([
            'document' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], [
            'document.required' => 'Debes seleccionar un archivo.',
            'document.mimes'    => 'Solo se aceptan imágenes (JPG, PNG, WEBP) o PDF.',
            'document.max'      => 'El archivo no debe superar 10 MB.',
        ]);

        $field = $fieldMap[$type];

        // Borrar el archivo anterior si existía
        if ($subject->$field) {
            Storage::delete($subject->$field);
        }

        $path = $request->file('document')->store($dirMap[$type]);
        $subject->update([$field => $path]);

        activity()
            ->performedOn($subject)
            ->causedBy(auth()->user())
            ->log("Documento '{$type}' subido manualmente por " . auth()->user()->name);

        return back()->with('success', 'Documento subido correctamente.');
    }

    /**
     * Borra un documento del sujeto y resetea el enrolamiento si aplica.
     * DELETE /tenant/subjects/{id}/document/{type}
     */
    public function deleteDocument($id, string $type)
    {
        $subject = Subject::findOrFail($id);

        $fieldMap = [
            'ine_front'        => 'ine_front_path',
            'ine_back'         => 'ine_back_path',
            'selfie'           => 'selfie_path',
            'consent'          => 'consent_document_path',
            'proof_of_address' => 'proof_of_address_path',
        ];

        if (!isset($fieldMap[$type])) {
            return back()->with('error', 'Tipo de documento no válido.');
        }

        $field = $fieldMap[$type];

        if ($subject->$field) {
            Storage::delete($subject->$field);
            $subject->update([$field => null]);
        }

        // Si se borraron los 3 docs de identidad principal → resetear enrolamiento
        // para permitir un nuevo proceso desde el enlace de enrolamiento.
        $identityDocs = ['ine_front_path', 'ine_back_path', 'selfie_path'];
        $subject->refresh();
        $allGone = collect($identityDocs)->every(fn($f) => empty($subject->$f));

        if ($allGone && $subject->enrollment_completed_at) {
            $subject->update([
                'enrollment_completed_at' => null,
                'enrollment_tc_accepted_at' => null,
            ]);
        }

        activity()
            ->performedOn($subject)
            ->causedBy(auth()->user())
            ->log("Documento '{$type}' eliminado por " . auth()->user()->name);

        return back()->with('success', 'Documento eliminado correctamente.');
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
            'project_id'           => 'required|exists:projects,id',
            'tipo'                 => 'required|string|in:persona_fisica,persona_moral',
            'name_or_company'      => 'required|string|max:255',
            'rfc' => [
                'required', 'string', 'min:12', 'max:13',
                'regex:/^([A-ZÑ&]{3,4}) ?(?:- ?)?(\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])) ?(?:- ?)?([A-Z\d]{2}[A\d])$/i'
            ],
            'curp' => [
                'nullable', 'required_if:tipo,persona_fisica', 'string', 'size:18',
                'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]\d$/i'
            ],
            'address'              => 'nullable|string|max:500',
            'consent_legal_basis'  => 'required|string|max:255',
            'consent_document'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'ine_front'            => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'ine_back'             => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            // Tier 2
            'username'             => 'nullable|string|max:100',
            'nss'                  => 'nullable|digits:11',
            'proof_of_address'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'credit_consent_granted' => 'nullable|boolean',
        ]);

        $consentPath = $subject->consent_document_path;
        if ($request->hasFile('consent_document')) {
            if ($consentPath) Storage::delete($consentPath);
            $consentPath = $request->file('consent_document')->store('consent_documents');
        }

        $ineFrontPath = $subject->ine_front_path;
        if ($request->hasFile('ine_front')) {
            if ($ineFrontPath) Storage::delete($ineFrontPath);
            $ineFrontPath = $request->file('ine_front')->store('ine_documents');
        }

        $ineBackPath = $subject->ine_back_path;
        if ($request->hasFile('ine_back')) {
            if ($ineBackPath) Storage::delete($ineBackPath);
            $ineBackPath = $request->file('ine_back')->store('ine_documents');
        }

        // Tier 2: Comprobante de domicilio
        $proofOfAddressPath = $subject->proof_of_address_path;
        if ($request->hasFile('proof_of_address')) {
            if ($proofOfAddressPath) Storage::delete($proofOfAddressPath);
            $proofOfAddressPath = $request->file('proof_of_address')->store('proof_of_address_documents');
        }

        // Consentimiento crediticio: solo se activa, nunca se revoca via update form
        $creditConsent       = $subject->credit_consent_granted;
        $creditConsentAt     = $subject->credit_consent_at;
        if (!$creditConsent && $request->boolean('credit_consent_granted')) {
            $creditConsent   = true;
            $creditConsentAt = now();
        }

        $subject->update([
            'project_id'             => $request->project_id,
            'tipo'                   => $request->tipo,
            'name_or_company'        => $request->name_or_company,
            'rfc'                    => strtoupper($request->rfc),
            'curp'                   => $request->curp ? strtoupper($request->curp) : null,
            'address'                => $request->address,
            'consent_legal_basis'    => $request->consent_legal_basis,
            'consent_document_path'  => $consentPath,
            'ine_front_path'         => $ineFrontPath,
            'ine_back_path'          => $ineBackPath,
            // Tier 2
            'username'               => $request->username ?: $subject->username,
            'nss'                    => $request->nss ?: $subject->nss,
            'proof_of_address_path'  => $proofOfAddressPath,
            'credit_consent_granted' => $creditConsent,
            'credit_consent_at'      => $creditConsentAt,
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
     * Regenerar el token de enrolamiento del sujeto (genera nuevo UUID con 24h de expiración).
     */
    public function regenerateEnrollment($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->generateEnrollmentToken();

        activity()
            ->performedOn($subject)
            ->causedBy(auth()->user())
            ->log("Token de enrolamiento regenerado para: {$subject->name_or_company}");

        return back()->with('success', 'Enlace de enrolamiento regenerado. El nuevo enlace es válido por 24 horas.');
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
        if ($subject->ine_front_path) {
            Storage::delete($subject->ine_front_path);
        }
        if ($subject->ine_back_path) {
            Storage::delete($subject->ine_back_path);
        }
        if ($subject->selfie_path) {
            Storage::delete($subject->selfie_path);
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
