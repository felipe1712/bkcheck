@extends('layouts.master')
@section('title') Editar Usuario @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Editar Usuario</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tenant.users.index') }}">Usuarios</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-xxl-8">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Modificar Datos de: {{ $user->name }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('tenant.users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" placeholder="Nombre y Apellido" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" placeholder="ejemplo@empresa.com" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Rol dentro de la Organización</label>
                        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                            <option value="">Seleccione un rol...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ (old('role') == $role->name || $user->hasRole($role->name)) ? 'selected' : '' }}>
                                    @if($role->name == 'tenant_admin')
                                        Administrador de Cliente (Acceso total)
                                    @elseif($role->name == 'investigador')
                                        Investigador (Gestión de proyectos y sujetos)
                                    @else
                                        {{ $role->name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="card border-dashed mt-4 mb-3">
                        <div class="card-body">
                            <h5 class="fs-14 mb-1 text-warning"><i class="ri-alert-line align-middle me-1"></i> Actualizar Contraseña (Opcional)</h5>
                            <p class="text-muted fs-13">Deje estos campos vacíos si no desea cambiar la contraseña actual del usuario.</p>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Nueva Contraseña</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Mínimo 8 caracteres">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Repita la nueva contraseña">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('tenant.users.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
