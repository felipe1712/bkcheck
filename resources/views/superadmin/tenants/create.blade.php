@extends('layouts.master')
@section('title') Registrar Cliente @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Registrar Nuevo Cliente</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.tenants.index') }}">Clientes</a></li>
                    <li class="breadcrumb-item active">Registrar</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-xxl-8">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Información del Cliente y Administrador Inicial</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('superadmin.tenants.store') }}" method="POST">
                    @csrf

                    <h5 class="text-primary mb-3">Datos de la Empresa (Cliente)</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nombre Comercial de la Empresa</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Ej: Consultores de Identidad S.C." required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="limite_consultas_mensual" class="form-label">Límite Mensual de Consultas de API</label>
                            <input type="number" class="form-control @error('limite_consultas_mensual') is-invalid @enderror" id="limite_consultas_mensual" name="limite_consultas_mensual" value="{{ old('limite_consultas_mensual', 100) }}" min="1" required>
                            @error('limite_consultas_mensual')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <h5 class="text-primary mt-4 mb-3">Datos del Administrador Inicial</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="admin_name" class="form-label">Nombre del Administrador</label>
                            <input type="text" class="form-control @error('admin_name') is-invalid @enderror" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" placeholder="Ej: Juan Pérez" required>
                            @error('admin_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="admin_email" class="form-label">Correo Electrónico (Será su usuario)</label>
                            <input type="email" class="form-control @error('admin_email') is-invalid @enderror" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" placeholder="admin@empresa.com" required>
                            @error('admin_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="admin_password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control @error('admin_password') is-invalid @enderror" id="admin_password" name="admin_password" placeholder="Contraseña de acceso" required>
                            @error('admin_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="admin_password_confirmation" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="admin_password_confirmation" name="admin_password_confirmation" placeholder="Repita la contraseña" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Registrar Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
