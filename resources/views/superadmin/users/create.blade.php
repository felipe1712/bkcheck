@extends('layouts.master')
@section('title') Crear Usuario @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Crear Nuevo Usuario</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.users.index') }}">Usuarios</a></li>
                    <li class="breadcrumb-item active">Crear</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-xxl-8">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Detalles del Usuario</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('superadmin.users.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Nombre Completo</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Nombre y Apellido" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="ejemplo@empresa.com" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label fw-semibold">Rol del Usuario</label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="">Seleccione un rol...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                        @if($role->name == 'super_admin')
                                            Super Administrador Global
                                        @elseif($role->name == 'tenant_admin')
                                            Administrador de Cliente
                                        @elseif($role->name == 'investigador')
                                            Investigador
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

                        <div class="col-md-6 mb-3">
                            <label for="tenant_id" class="form-label fw-semibold">Cliente</label>
                            <select class="form-select @error('tenant_id') is-invalid @enderror" id="tenant_id" name="tenant_id">
                                <option value="">Global (Sin Cliente - Para Super Admin)</option>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                                        {{ $tenant->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tenant_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted fs-11 mt-1">
                                Nota: Los Super Administradores deben ser "Global". Los roles de Administrador de Cliente e Investigador requieren seleccionar un Cliente.
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label fw-semibold">Contraseña</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Mínimo 8 caracteres" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Repita la contraseña" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('superadmin.users.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Premium dynamic helper: automatically pre-select/disable options based on selections
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        const tenantSelect = document.getElementById('tenant_id');

        function adjustDropdowns() {
            const selectedRole = roleSelect.value;
            if (selectedRole === 'super_admin') {
                tenantSelect.value = "";
                tenantSelect.setAttribute('disabled', 'disabled');
            } else {
                tenantSelect.removeAttribute('disabled');
            }
        }

        roleSelect.addEventListener('change', adjustDropdowns);
        adjustDropdowns(); // Run on load in case of validation redirect with old input
        
        // Remove disabled attribute on submit so it is submitted in case it was disabled
        document.querySelector('form').addEventListener('submit', function() {
            tenantSelect.removeAttribute('disabled');
        });
    });
</script>
@endsection
