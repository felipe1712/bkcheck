<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:tenant_admin|investigador']);
    }

    /**
     * Display a listing of the projects.
     */
    public function index()
    {
        $projects = Project::paginate(10);
        return view('tenant.projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        return view('tenant.projects.create');
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // Log activity
        activity()
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->log("Proyecto creado: {$project->name}");

        return redirect()->route('tenant.projects.index')
            ->with('success', 'Proyecto creado correctamente.');
    }

    /**
     * Display the specified project and its subjects.
     */
    public function show($id)
    {
        $project = Project::findOrFail($id);
        
        // Retrieve subjects inside this project (automatically isolated by HasTenant trait)
        $subjects = \App\Models\Subject::where('project_id', $project->id)->paginate(10);

        return view('tenant.projects.show', compact('project', 'subjects'));
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit($id)
    {
        $project = Project::findOrFail($id);
        return view('tenant.projects.edit', compact('project'));
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update($request->only('name', 'description'));

        // Log activity
        activity()
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->log("Proyecto actualizado: {$project->name}");

        return redirect()->route('tenant.projects.index')
            ->with('success', 'Proyecto actualizado correctamente.');
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        
        // Log activity before deletion
        activity()
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->log("Proyecto eliminado: {$project->name}");

        $project->delete();

        return redirect()->route('tenant.projects.index')
            ->with('success', 'Proyecto eliminado correctamente.');
    }
}
