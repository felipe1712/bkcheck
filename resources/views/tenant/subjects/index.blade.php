@extends('layouts.master')
@section('title') Sujetos de Investigación @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Listado General de Sujetos</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Sujetos</li>
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
                <h5 class="card-title mb-0 flex-grow-1">Historial General de Sujetos Consultados</h5>
                <div class="flex-shrink-0">
                    <a href="{{ route('tenant.subjects.create') }}" class="btn btn-primary btn-sm"><i class="ri-user-add-line align-bottom me-1"></i> Registrar Sujeto</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-hover table-centered align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Nombre / Razón Social</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">RFC</th>
                                <th scope="col">Proyecto</th>
                                <th scope="col">Fecha de Registro</th>
                                <th scope="col">Consentimiento</th>
                                <th scope="col">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subjects as $subj)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs flex-shrink-0 me-2">
                                            <span class="avatar-title bg-light text-primary rounded fs-13">
                                                {{ $subj->tipo == 'persona_fisica' ? 'PF' : 'PM' }}
                                            </span>
                                        </div>
                                        <span class="fw-semibold">{{ $subj->name_or_company }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $subj->tipo == 'persona_fisica' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary' }}">
                                        {{ $subj->tipo == 'persona_fisica' ? 'Física' : 'Moral' }}
                                    </span>
                                </td>
                                <td><code>{{ $subj->rfc }}</code></td>
                                <td>
                                    <a href="{{ route('tenant.projects.show', $subj->project->id) }}" class="text-primary">{{ $subj->project->name }}</a>
                                </td>
                                <td>{{ $subj->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="ri-check-line align-middle me-1"></i> Autorizado
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('tenant.subjects.show', $subj->id) }}" class="btn btn-sm btn-soft-primary">
                                        <i class="ri-eye-line"></i> Abrir Expediente
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay sujetos registrados en la base de datos.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $subjects->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
