@extends('layouts.master')
@section('title') Registrar Sujeto @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Registrar Sujeto de Investigación</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tenant.subjects.index') }}">Sujetos</a></li>
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
                <h4 class="card-title mb-0 flex-grow-1">Proceso de Registro y Consentimiento Obligatorio (Wizard)</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('tenant.subjects.store') }}" method="POST" enctype="multipart/form-data" id="subjectForm">
                    @csrf

                    <!-- Step 1: Datos Básicos del Sujeto -->
                    <div id="step-1">
                        <div class="d-flex align-items-center mb-4 border-bottom pb-2">
                            <span class="avatar-title bg-primary text-white rounded-circle fs-16 me-2" style="width: 28px; height: 28px; display: inline-flex; justify-content: center; align-items: center;">1</span>
                            <h5 class="text-primary mb-0">Información de Identidad del Sujeto</h5>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="project_id" class="form-label">Proyecto Asociado</label>
                                <select class="form-select @error('project_id') is-invalid @enderror" id="project_id" name="project_id" required>
                                    <option value="" disabled selected>Seleccione un proyecto...</option>
                                    @foreach($projects as $proj)
                                        <option value="{{ $proj->id }}" {{ (old('project_id') == $proj->id || $selectedProjectId == $proj->id) ? 'selected' : '' }}>
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
                                    <option value="persona_fisica" {{ old('tipo') == 'persona_fisica' ? 'selected' : '' }}>Persona Física</option>
                                    <option value="persona_moral" {{ old('tipo', 'persona_moral') == 'persona_moral' ? 'selected' : '' }}>Persona Moral</option>
                                </select>
                                @error('tipo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name_or_company" class="form-label" id="nameLabel">Razón Social / Nombre Legal</label>
                                <input type="text" class="form-control @error('name_or_company') is-invalid @enderror" id="name_or_company" name="name_or_company" value="{{ old('name_or_company') }}" placeholder="Ej: Aceros de México S.A. de C.V." required>
                                @error('name_or_company')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="rfc" class="form-label">RFC (Registro Federal de Contribuyentes)</label>
                                <input type="text" class="form-control @error('rfc') is-invalid @enderror" id="rfc" name="rfc" value="{{ old('rfc') }}" placeholder="Ej: AME120304XYZ" style="text-transform: uppercase;" required>
                                <small class="text-muted">12 dígitos para empresas, 13 dígitos para personas físicas.</small>
                                @error('rfc')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3" id="curpGroup" style="display: none;">
                            <div class="col-md-6">
                                <label for="curp" class="form-label">CURP (Clave Única de Registro de Población)</label>
                                <input type="text" class="form-control @error('curp') is-invalid @enderror" id="curp" name="curp" value="{{ old('curp') }}" placeholder="Ej: PEJU900101HDFLNR01" style="text-transform: uppercase;">
                                <small class="text-muted">Requerido únicamente para Persona Física (18 caracteres).</small>
                                @error('curp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3" id="ineGroup" style="display: none;">
                            <div class="col-md-6">
                                <label for="ine_front" class="form-label">Identificación INE Frente (Imagen)</label>
                                <input type="file" class="form-control @error('ine_front') is-invalid @enderror" id="ine_front" name="ine_front" accept="image/*">
                                <small class="text-muted">Imagen frontal de la credencial (Max 5MB).</small>
                                @error('ine_front')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="ine_back" class="form-label">Identificación INE Reverso (Imagen)</label>
                                <input type="file" class="form-control @error('ine_back') is-invalid @enderror" id="ine_back" name="ine_back" accept="image/*">
                                <small class="text-muted">Imagen trasera de la credencial (Max 5MB).</small>
                                @error('ine_back')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label">Dirección Fiscal / Domicilio</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2" placeholder="Calle, Número, Colonia, C.P., Ciudad y Estado...">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4" id="usernameGroup" style="display:none;">
                            <label for="username" class="form-label">
                                <i class="ri-at-line me-1 text-muted"></i>
                                Username / Alias en Redes Sociales
                                <span class="badge bg-info-subtle text-info fs-10 ms-1">OSINT</span>
                            </label>
                            <input type="text"
                                class="form-control @error('username') is-invalid @enderror"
                                id="username" name="username"
                                value="{{ old('username') }}"
                                placeholder="ej: jperez (sin el @)"
                                maxlength="100">
                            <div class="form-text text-muted">
                                <i class="ri-information-line me-1"></i>
                                Opcional. Si se proporciona, se usará para buscar la presencia digital del investigado en más de 300 plataformas.
                            </div>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ─── Tier 2: NSS, Comprobante de Domicilio, Score Crediticio ─── --}}
                        <div id="tier2Group" style="display:none;">
                            <div class="d-flex align-items-center mb-3 mt-2 border-bottom pb-2">
                                <i class="ri-shield-check-line me-2 text-success fs-16"></i>
                                <span class="fw-semibold text-muted fs-13">Datos adicionales de verificación (Tier 2)</span>
                            </div>

                            <div class="row mb-3">
                                {{-- NSS --}}
                                <div class="col-md-6">
                                    <label for="nss" class="form-label">
                                        Número de Seguridad Social (NSS/IMSS)
                                    </label>
                                    <input type="text"
                                        class="form-control @error('nss') is-invalid @enderror"
                                        id="nss" name="nss"
                                        value="{{ old('nss') }}"
                                        placeholder="Ej: 12345678901"
                                        maxlength="11">
                                    <div class="form-text text-muted fs-12">Opcional. Permite consultar historial laboral IMSS. Se almacena encriptado.</div>
                                    @error('nss')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Comprobante de domicilio --}}
                                <div class="col-md-6">
                                    <label for="proof_of_address" class="form-label">
                                        Comprobante de Domicilio (CFE, Telmex, agua…)
                                    </label>
                                    <input type="file"
                                        class="form-control @error('proof_of_address') is-invalid @enderror"
                                        id="proof_of_address" name="proof_of_address"
                                        accept="image/*,application/pdf">
                                    <div class="form-text text-muted fs-12">Opcional. Imagen o PDF (máx 5 MB). Se extrae el domicilio vía OCR.</div>
                                    @error('proof_of_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Consentimiento explícito Buró de Crédito --}}
                            <div class="card border border-warning-subtle bg-warning-subtle mb-3 p-3" id="creditConsentGroup" style="display:none;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                        id="credit_consent_granted"
                                        name="credit_consent_granted"
                                        value="1"
                                        {{ old('credit_consent_granted') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="credit_consent_granted">
                                        <i class="ri-bank-card-line me-1 text-warning"></i>
                                        El investigado autorizó expresamente la consulta de su historial crediticio al Buró de Crédito (LRSIC Art. 28).
                                    </label>
                                </div>
                                <div class="form-text text-muted fs-12 mt-1">
                                    <i class="ri-alert-line me-1 text-warning"></i>
                                    La Ley para Regular las Sociedades de Información Crediticia exige autorización expresa y específica.
                                    Sin este check, el conector de score crediticio no se ejecutará.
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Step 2: Consentimiento Legal Obligatorio -->
                    <div id="step-2" class="mt-4">
                        <div class="d-flex align-items-center mb-4 border-bottom pb-2">
                            <span class="avatar-title bg-primary text-white rounded-circle fs-16 me-2" style="width: 28px; height: 28px; display: inline-flex; justify-content: center; align-items: center;">2</span>
                            <h5 class="text-primary mb-0">Cumplimiento de Privacidad y Consentimiento Expreso</h5>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="consent_legal_basis" class="form-label">Base Legal / Finalidad de la Consulta</label>
                                <select class="form-select @error('consent_legal_basis') is-invalid @enderror" id="consent_legal_basis" name="consent_legal_basis" required>
                                    <option value="" disabled selected>Seleccione la base legal...</option>
                                    <option value="Alta de Proveedores (Cumplimiento Comercial/KYB)" {{ old('consent_legal_basis') == 'Alta de Proveedores (Cumplimiento Comercial/KYB)' ? 'selected' : '' }}>Alta de Proveedores (Cumplimiento Comercial/KYB)</option>
                                    <option value="Proceso de Selección de Personal (Candidatos)" {{ old('consent_legal_basis') == 'Proceso de Selección de Personal (Candidatos)' ? 'selected' : '' }}>Proceso de Selección de Personal (Candidatos)</option>
                                    <option value="Auditoría Interna y Prevención de Lavado de Dinero (PLD)" {{ old('consent_legal_basis') == 'Auditoría Interna y Prevención de Lavado de Dinero (PLD)' ? 'selected' : '' }}>Auditoría Interna y Prevención de Lavado de Dinero (PLD)</option>
                                    <option value="Evaluación de Riesgo de Crédito (Financiamiento)" {{ old('consent_legal_basis') == 'Evaluación de Riesgo de Crédito (Financiamiento)' ? 'selected' : '' }}>Evaluación de Riesgo de Crédito (Financiamiento)</option>
                                    <option value="Otros fines comerciales legítimos" {{ old('consent_legal_basis') == 'Otros fines comerciales legítimos' ? 'selected' : '' }}>Otros fines comerciales legítimos</option>
                                </select>
                                @error('consent_legal_basis')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="consent_document" class="form-label">Subir Carta de Consentimiento Firmada (PDF / Imagen)</label>
                                <input type="file" class="form-control @error('consent_document') is-invalid @enderror" id="consent_document" name="consent_document" accept="application/pdf,image/*">
                                <small class="text-muted">Suba el formato firmado por el sujeto autorizando la búsqueda (Max 5MB).</small>
                                @error('consent_document')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Checkbox Obligatorio -->
                        <div class="form-check mb-4 bg-light p-3 rounded border border-warning-subtle">
                            <input class="form-check-input @error('consent_granted') is-invalid @enderror" type="checkbox" value="1" id="consent_granted" name="consent_granted" {{ old('consent_granted') ? 'checked' : '' }} required style="margin-left: 0; margin-right: 0.5rem; float: left;">
                            <label class="form-check-label text-wrap" for="consent_granted" style="font-weight: 500; font-size: 13.5px; padding-left: 20px; display: block;">
                                Confirmo que el sujeto (persona física o moral) ha otorgado su consentimiento expreso, libre e informado para realizar esta búsqueda de antecedentes y consultas de registros públicos bajo el amparo de la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP).
                            </label>
                            @error('consent_granted')
                                <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ $selectedProjectId ? route('tenant.projects.show', $selectedProjectId) : route('tenant.subjects.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn"><i class="ri-check-line align-bottom me-1"></i> Registrar y Validar</button>
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
        var ineGroup = document.getElementById('ineGroup');
        var usernameGroup = document.getElementById('usernameGroup');
        var tier2Group = document.getElementById('tier2Group');

        if (tipo === 'persona_fisica') {
            curpGroup.style.display = 'block';
            curpInput.setAttribute('required', 'required');
            ineGroup.style.display = 'flex';
            if (usernameGroup) usernameGroup.style.display = 'block';
            if (tier2Group) tier2Group.style.display = 'block';
            nameLabel.innerText = 'Nombre Completo (Persona Física)';
            document.getElementById('name_or_company').placeholder = 'Ej: Juan Pérez López';
            document.getElementById('rfc').placeholder = 'Ej: PELJ900101XYZ';
        } else {
            curpGroup.style.display = 'none';
            curpInput.removeAttribute('required');
            curpInput.value = '';
            ineGroup.style.display = 'none';
            if (usernameGroup) usernameGroup.style.display = 'none';
            if (tier2Group) tier2Group.style.display = 'none';
            nameLabel.innerText = 'Razón Social / Nombre Legal';
            document.getElementById('name_or_company').placeholder = 'Ej: Aceros de México S.A. de C.V.';
            document.getElementById('rfc').placeholder = 'Ej: AME120304XYZ';
        }
    }

    // Mostrar el consentimiento crediticio solo cuando la base legal es crédito
    var legalBasisSelect = document.getElementById('consent_legal_basis');
    if (legalBasisSelect) {
        legalBasisSelect.addEventListener('change', function() {
            var creditGroup = document.getElementById('creditConsentGroup');
            if (creditGroup) {
                creditGroup.style.display =
                    this.value.toLowerCase().includes('crédito') ||
                    this.value.toLowerCase().includes('credito') ||
                    this.value.toLowerCase().includes('financiamiento')
                    ? 'block' : 'none';
            }
        });
    }


    // Call on load to set fields
    document.addEventListener("DOMContentLoaded", function() {
        toggleSubjectTypeFields();
    });
</script>
@endsection
