@extends('layouts.master')
@section('title') Editar Cliente @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Editar Cliente: {{ $tenant->name }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.tenants.index') }}">Clientes</a></li>
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
                <h4 class="card-title mb-0 flex-grow-1">Información de la Cuenta</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('superadmin.tenants.update', $tenant->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nombre Comercial de la Empresa</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $tenant->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="limite_consultas_mensual" class="form-label">Límite Mensual de Consultas de API</label>
                            <input type="number" class="form-control @error('limite_consultas_mensual') is-invalid @enderror" id="limite_consultas_mensual" name="limite_consultas_mensual" value="{{ old('limite_consultas_mensual', $tenant->limite_consultas_mensual) }}" min="1" required>
                            @error('limite_consultas_mensual')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="activo" class="form-label">Estatus de la Cuenta</label>
                            <select class="form-select @error('activo') is-invalid @enderror" id="activo" name="activo" required>
                                <option value="1" {{ $tenant->activo ? 'selected' : '' }}>Activo / Operando</option>
                                <option value="0" {{ !$tenant->activo ? 'selected' : '' }}>Inactivo / Suspendido</option>
                            </select>
                            @error('activo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
