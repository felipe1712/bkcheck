@extends('layouts.master')
@section('title') Clientes @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Administración de Clientes</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Clientes</li>
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
                <h5 class="card-title mb-0 flex-grow-1">Listado de Clientes Registrados</h5>
                <div class="flex-shrink-0">
                    <a href="{{ route('superadmin.tenants.create') }}" class="btn btn-primary btn-sm"><i class="ri-add-line align-bottom me-1"></i> Registrar Nuevo Cliente</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-nowrap table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nombre Comercial</th>
                                <th scope="col">Usuarios</th>
                                <th scope="col">Límite Consultas / Mes</th>
                                <th scope="col">Estatus</th>
                                <th scope="col">Creado el</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenants as $tenant)
                            <tr>
                                <td class="fw-semibold">#{{ $tenant->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs flex-shrink-0 me-2">
                                            <span class="avatar-title bg-primary-subtle rounded text-primary fs-14">
                                                {{ substr($tenant->name, 0, 2) }}
                                            </span>
                                        </div>
                                        <span class="fw-medium">{{ $tenant->name }}</span>
                                    </div>
                                </td>
                                <td><a href="{{ route('superadmin.users.index', ['tenant_id' => $tenant->id]) }}" class="badge bg-primary text-white fs-12">{{ $tenant->users_count }} usuarios</a></td>
                                <td>{{ $tenant->limite_consultas_mensual }}</td>
                                <td>
                                    @if($tenant->activo)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger">Inactivo/Suspendido</span>
                                    @endif
                                </td>
                                <td>{{ $tenant->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('superadmin.tenants.edit', $tenant->id) }}" class="btn btn-sm btn-soft-primary">
                                            <i class="ri-edit-line"></i> Editar
                                        </a>
                                        <form action="{{ route('superadmin.tenants.destroy', $tenant->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de cambiar el estatus de este cliente?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm {{ $tenant->activo ? 'btn-soft-danger' : 'btn-soft-success' }}">
                                                <i class="{{ $tenant->activo ? 'ri-close-circle-line' : 'ri-checkbox-circle-line' }}"></i>
                                                {{ $tenant->activo ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay clientes registrados.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    {{ $tenants->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
