@extends('layouts.master')
@section('title') Gestión de Usuarios @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Administración de Usuarios</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Usuarios</li>
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

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <div class="col-lg-12">
        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('superadmin.users.index') }}" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label for="tenant_filter" class="form-label fw-semibold">Filtrar por Cliente</label>
                        <select name="tenant_id" id="tenant_filter" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Todos los Clientes --</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}" {{ $tenantId == $tenant->id ? 'selected' : '' }}>
                                    {{ $tenant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end mt-4">
                        @if($tenantId)
                            <a href="{{ route('superadmin.users.index') }}" class="btn btn-light w-100">
                                <i class="ri-refresh-line align-bottom me-1"></i> Limpiar Filtro
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 align-items-center d-flex">
                <h5 class="card-title mb-0 flex-grow-1">Lista de Todos los Usuarios</h5>
                <div class="flex-shrink-0">
                    <a href="{{ route('superadmin.users.create') }}" class="btn btn-primary btn-sm"><i class="ri-add-line align-bottom me-1"></i> Crear Nuevo Usuario</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-nowrap table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Email</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Rol</th>
                                <th scope="col">Estatus</th>
                                <th scope="col">Fecha de Registro</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td class="fw-semibold">#{{ $user->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs flex-shrink-0 me-2">
                                            <span class="avatar-title bg-primary-subtle rounded-circle text-primary fs-14">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </span>
                                        </div>
                                        <span class="fw-semibold">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->tenant)
                                        <span class="badge bg-light text-dark border">{{ $user->tenant->name }}</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Global (Super Admin)</span>
                                    @endif
                                </td>
                                <td>
                                    @foreach($user->roles as $role)
                                        @if($role->name == 'super_admin')
                                            <span class="badge bg-dark text-white">Super Administrador</span>
                                        @elseif($role->name == 'tenant_admin')
                                            <span class="badge bg-info-subtle text-info">Administrador Cliente</span>
                                        @elseif($role->name == 'investigador')
                                            <span class="badge bg-success-subtle text-success">Investigador</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $role->name }}</span>
                                        @endif
                                    @endforeach
                                </td>
                                <td>
                                    @if($user->activo)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger">Bloqueado</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('superadmin.users.edit', $user->id) }}" class="btn btn-sm btn-soft-primary">
                                            <i class="ri-edit-line"></i> Editar
                                        </a>

                                        @if($user->id !== Auth::id())
                                        <!-- Toggle Status -->
                                        <form action="{{ route('superadmin.users.toggle-status', $user->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $user->activo ? 'btn-soft-warning' : 'btn-soft-success' }}" 
                                                    title="{{ $user->activo ? 'Bloquear usuario' : 'Desbloquear usuario' }}">
                                                <i class="{{ $user->activo ? 'ri-user-unfollow-line' : 'ri-user-follow-line' }}"></i> 
                                                {{ $user->activo ? 'Bloquear' : 'Desbloquear' }}
                                            </button>
                                        </form>

                                        <!-- Delete -->
                                        <form action="{{ route('superadmin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este usuario permanentemente?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-soft-danger">
                                                <i class="ri-delete-bin-line"></i> Eliminar
                                            </button>
                                        </form>
                                        @else
                                        <span class="text-muted fs-12 italic">Tu usuario</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No se encontraron usuarios.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
