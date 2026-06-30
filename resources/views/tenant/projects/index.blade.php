@extends('layouts.master')
@section('title') Proyectos @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Mis Proyectos de Investigación</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Proyectos</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-0 align-items-center d-flex">
                <h5 class="card-title mb-0 flex-grow-1">Proyectos de Investigación</h5>
                <div class="flex-shrink-0">
                    <a href="{{ route('tenant.projects.create') }}" class="btn btn-primary btn-sm"><i class="ri-add-line align-bottom me-1"></i> Crear Proyecto</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-nowrap table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nombre del Proyecto</th>
                                <th scope="col">Descripción</th>
                                <th scope="col">Creado el</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $proj)
                            <tr>
                                <td class="fw-semibold">#{{ $proj->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs flex-shrink-0 me-2">
                                            <span class="avatar-title bg-primary-subtle rounded text-primary fs-14">
                                                <i class="ri-folder-open-line"></i>
                                            </span>
                                        </div>
                                        <span class="fw-semibold">{{ $proj->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted text-wrap d-block" style="max-width: 300px;">{{ Str::limit($proj->description ?? 'Sin descripción', 80) }}</span>
                                </td>
                                <td>{{ $proj->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('tenant.projects.show', $proj->id) }}" class="btn btn-sm btn-soft-info">
                                            <i class="ri-eye-line"></i> Abrir
                                        </a>
                                        <a href="{{ route('tenant.projects.edit', $proj->id) }}" class="btn btn-sm btn-soft-primary">
                                            <i class="ri-edit-line"></i> Editar
                                        </a>
                                        <form action="{{ route('tenant.projects.destroy', $proj->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este proyecto y todos sus sujetos asociados?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-soft-danger">
                                                <i class="ri-delete-bin-line"></i> Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay proyectos creados aún.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    {{ $projects->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
