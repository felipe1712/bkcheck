@extends('layouts.master')
@section('title') Editar Sujeto @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Editar Sujeto: {{ $subject->name_or_company }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tenant.subjects.index') }}">Sujetos</a></li>
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
                <h4 class="card-title mb-0 flex-grow-1">Información General del Sujeto</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('tenant.subjects.update', $subject->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="project_id" class="form-label">Proyecto Asociado</label>
                            <select class="form-select @error('project_id') is-invalid @enderror" id="project_id" name="project_id" required>
                                @foreach($projects as $proj)
                                    <option value="{{ $proj->id }}" {{ old('project_id', $subject->project_id) == $proj->id ? 'selected' : '' }}>
                                        {{ $proj->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('project_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo de Contribuyente</label>
                            <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" onchange="toggleSubjectTypeFields()" required>
                                <option value="persona_fisica" {{ old('tipo', $subject->tipo) == 'persona_fisica' ? 'selected' : '' }}>Persona Física</option>
                                <option value="persona_moral" {{ old('tipo', $subject->tipo) == 'persona_moral' ? 'selected' : '' }}>Persona Moral</option>
                            </select>
                            @error('tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name_or_company" class="form-label" id="nameLabel">Razón Social / Nombre Legal</label>
                            <input type="text" class="form-control @error('name_or_company') is-invalid @enderror" id="name_or_company" name="name_or_company" value="{{ old('name_or_company', $subject->name_or_company) }}" required>
                            @error('name_or_company')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="rfc" class="form-label">RFC (Registro Federal de Contribuyentes)</label>
                            <input type="text" class="form-control @error('rfc') is-invalid @enderror" id="rfc" name="rfc" value="{{ old('rfc', $subject->rfc) }}" required style="text-transform: uppercase;">
                            @error('rfc')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3" id="curpGroup" style="display: none;">
                        <div class="col-md-6">
                            <label for="curp" class="form-label">CURP</label>
                            <input type="text" class="form-control @error('curp') is-invalid @enderror" id="curp" name="curp" value="{{ old('curp', $subject->curp) }}" style="text-transform: uppercase;">
                            @error('curp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección Fiscal / Domicilio</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $subject->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <h5 class="text-primary mt-4 mb-3">Cumplimiento y Documento</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="consent_legal_basis" class="form-label">Base Legal / Finalidad</label>
                            <input type="text" class="form-control @error('consent_legal_basis') is-invalid @enderror" id="consent_legal_basis" name="consent_legal_basis" value="{{ old('consent_legal_basis', $subject->consent_legal_basis) }}" required>
                            @error('consent_legal_basis')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="consent_document" class="form-label">Reemplazar Carta de Consentimiento Firmada</label>
                            <input type="file" class="form-control @error('consent_document') is-invalid @enderror" id="consent_document" name="consent_document" accept="application/pdf,image/*">
                            @if($subject->consent_document_path)
                                <small class="text-success mt-1 d-block"><i class="ri-attachment-line"></i> Documento de consentimiento actual registrado</small>
                            @endif
                            @error('consent_document')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('tenant.projects.show', $subject->project_id) }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSubjectTypeFields() {
        var tipo = document.getElementById('tipo').value;
        var curpGroup = document.getElementById('curpGroup');
        var curpInput = document.getElementById('curp');
        var nameLabel = document.getElementById('nameLabel');

        if (tipo === 'persona_fisica') {
            curpGroup.style.display = 'block';
            curpInput.setAttribute('required', 'required');
            nameLabel.innerText = 'Nombre Completo (Persona Física)';
        } else {
            curpGroup.style.display = 'none';
            curpInput.removeAttribute('required');
            nameLabel.innerText = 'Razón Social / Nombre Legal';
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        toggleSubjectTypeFields();
    });
</script>
@endsection
