@extends('layouts.master')
@section('title') Usuarios @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Usuarios de la Organización</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">Inicio</a></li>
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
        <div class="card">
            <div class="card-header border-0 align-items-center d-flex">
                <h5 class="card-title mb-0 flex-grow-1">Lista de Usuarios</h5>
                <div class="flex-shrink-0">
                    <a href="{{ route('tenant.users.create') }}" class="btn btn-primary btn-sm"><i class="ri-add-line align-bottom me-1"></i> Invitar/Crear Usuario</a>
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
                                <th scope="col">Roles</th>
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
                                    @foreach($user->roles as $role)
                                        @if($role->name == 'tenant_admin')
                                            <span class="badge bg-danger-subtle text-danger">Administrador Cliente</span>
                                        @elseif($role->name == 'investigador')
                                            <span class="badge bg-success-subtle text-success">Investigador</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $role->name }}</span>
                                        @endif
                                    @endforeach
                                </td>
                                <td>{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('tenant.users.edit', $user->id) }}" class="btn btn-sm btn-soft-primary">
                                            <i class="ri-edit-line"></i> Editar
                                        </a>
                                        @if($user->id !== Auth::id())
                                        <form action="{{ route('tenant.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-soft-danger">
                                                <i class="ri-delete-bin-line"></i> Eliminar
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay usuarios registrados.</td>
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
