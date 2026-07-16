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

                    {{-- ── Tipo y Proyecto ────────────────────────────────────────────── --}}
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
                            @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo de Contribuyente</label>
                            <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" onchange="toggleSubjectTypeFields()" required>
                                <option value="persona_fisica" {{ old('tipo', $subject->tipo) == 'persona_fisica' ? 'selected' : '' }}>Persona Física</option>
                                <option value="persona_moral"  {{ old('tipo', $subject->tipo) == 'persona_moral'  ? 'selected' : '' }}>Persona Moral</option>
                            </select>
                            @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- ── Nombre y RFC ────────────────────────────────────────────────── --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name_or_company" class="form-label" id="nameLabel">Razón Social / Nombre Legal</label>
                            <input type="text"
                                   class="form-control @error('name_or_company') is-invalid @enderror"
                                   id="name_or_company" name="name_or_company"
                                   value="{{ old('name_or_company', $subject->name_or_company) }}" required>
                            @error('name_or_company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="rfc" class="form-label">RFC (Registro Federal de Contribuyentes)</label>
                            <input type="text"
                                   class="form-control @error('rfc') is-invalid @enderror"
                                   id="rfc" name="rfc"
                                   value="{{ old('rfc', $subject->rfc) }}"
                                   required style="text-transform: uppercase;">
                            @error('rfc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- ── CURP (solo persona física) ──────────────────────────────────── --}}
                    <div class="row mb-3" id="curpGroup" style="display: none;">
                        <div class="col-md-6">
                            <label for="curp" class="form-label">CURP</label>
                            <input type="text"
                                   class="form-control @error('curp') is-invalid @enderror"
                                   id="curp" name="curp"
                                   value="{{ old('curp', $subject->curp) }}"
                                   maxlength="18" style="text-transform: uppercase;"
                                   placeholder="18 caracteres">
                            @error('curp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6" id="usernameGroup">
                            <label for="username" class="form-label">
                                Username / Alias Digital
                                <span class="badge bg-info-subtle text-info fs-11 ms-1">OSINT</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ri-at-line"></i></span>
                                <input type="text"
                                       class="form-control @error('username') is-invalid @enderror"
                                       id="username" name="username"
                                       value="{{ old('username', $subject->username) }}"
                                       placeholder="ej: jperez_mx" maxlength="100">
                            </div>
                            <div class="form-text">Se usará para búsqueda en redes sociales (Sherlock / Social Analyzer).</div>
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- ── INE Frente / Reverso (solo persona física) ──────────────────── --}}
                    <div class="row mb-3" id="ineGroup" style="display: none;">
                        <div class="col-md-6">
                            <label for="ine_front" class="form-label">Identificación INE Frente (Imagen)</label>
                            <input type="file"
                                   class="form-control @error('ine_front') is-invalid @enderror"
                                   id="ine_front" name="ine_front" accept="image/*">
                            @if($subject->ine_front_path)
                                <small class="text-success mt-1 d-block">
                                    <i class="ri-checkbox-circle-line"></i> Imagen frontal actual registrada
                                </small>
                            @endif
                            <small class="text-muted">Suba una nueva imagen para reemplazar la actual (Max 5MB).</small>
                            @error('ine_front')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="ine_back" class="form-label">Identificación INE Reverso (Imagen)</label>
                            <input type="file"
                                   class="form-control @error('ine_back') is-invalid @enderror"
                                   id="ine_back" name="ine_back" accept="image/*">
                            @if($subject->ine_back_path)
                                <small class="text-success mt-1 d-block">
                                    <i class="ri-checkbox-circle-line"></i> Imagen trasera actual registrada
                                </small>
                            @endif
                            <small class="text-muted">Suba una nueva imagen para reemplazar la actual (Max 5MB).</small>
                            @error('ine_back')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- ── Dirección ───────────────────────────────────────────────────── --}}
                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección Fiscal / Domicilio</label>
                        <textarea class="form-control @error('address') is-invalid @enderror"
                                  id="address" name="address" rows="2">{{ old('address', $subject->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- ══════════════════════════════════════════════════════════════════
                         SECCIÓN TIER 2 — Fuentes adicionales (solo persona física)
                         ══════════════════════════════════════════════════════════════════ --}}
                    <div id="tier2Group" style="display: none;">
                        <h5 class="text-primary mt-4 mb-1">
                            <i class="ri-shield-star-line me-2"></i>Fuentes Avanzadas (Tier 2)
                        </h5>
                        <p class="text-muted fs-13 mb-3">
                            Información adicional para consultas de CURP/RENAPO, domicilio, historial IMSS y score crediticio.
                        </p>

                        {{-- NSS --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nss" class="form-label">
                                    Número de Seguridad Social (NSS)
                                    <span class="badge bg-warning-subtle text-warning fs-11 ms-1">IMSS</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('nss') is-invalid @enderror"
                                       id="nss" name="nss"
                                       value="{{ old('nss', $subject->nss) }}"
                                       placeholder="11 dígitos" maxlength="11"
                                       pattern="\d{11}"
                                       inputmode="numeric">
                                <div class="form-text">Permite consultar historial laboral y semanas cotizadas ante el IMSS.</div>
                                @error('nss')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="proof_of_address" class="form-label">
                                    Comprobante de Domicilio
                                    <span class="badge bg-info-subtle text-info fs-11 ms-1">OCR</span>
                                </label>
                                <input type="file"
                                       class="form-control @error('proof_of_address') is-invalid @enderror"
                                       id="proof_of_address" name="proof_of_address"
                                       accept="application/pdf,image/jpeg,image/png">
                                @if($subject->proof_of_address_path)
                                    <small class="text-success mt-1 d-block">
                                        <i class="ri-checkbox-circle-line"></i> Comprobante actual registrado.
                                        Suba uno nuevo para reemplazarlo.
                                    </small>
                                @else
                                    <small class="text-muted">Recibo de luz, agua, teléfono o estado de cuenta (PDF o imagen, max 5MB).</small>
                                @endif
                                @error('proof_of_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Consentimiento Crediticio --}}
                        @if(!$subject->credit_consent_granted)
                        <div class="row mb-3" id="creditConsentGroup">
                            <div class="col-12">
                                <div class="alert alert-warning border-warning-subtle d-flex align-items-start gap-3 mb-0">
                                    <i class="ri-shield-keyhole-line fs-20 text-warning mt-1 flex-shrink-0"></i>
                                    <div>
                                        <p class="fw-semibold mb-1">Autorización de Consulta al Buró de Crédito</p>
                                        <p class="text-muted fs-13 mb-2">
                                            De conformidad con el artículo 28 de la Ley para Regular las Sociedades de Información Crediticia,
                                            se requiere autorización <strong>expresa y específica</strong> del titular para consultar
                                            su historial crediticio. Esta autorización quedará registrada con timestamp y es irrevocable.
                                        </p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   id="credit_consent_granted" name="credit_consent_granted"
                                                   value="1" {{ old('credit_consent_granted') ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="credit_consent_granted">
                                                El investigado otorga su autorización expresa para consultar su historial
                                                crediticio ante sociedades de información crediticia (Buró de Crédito / Círculo de Crédito).
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="alert alert-success border-success-subtle d-flex align-items-center gap-2 mb-0 py-2">
                                    <i class="ri-shield-check-line text-success fs-18"></i>
                                    <span class="fs-13">
                                        Autorización crediticia otorgada el
                                        <strong>{{ $subject->credit_consent_at?->format('d/m/Y H:i') ?? '—' }}</strong>.
                                        Esta autorización es irrevocable una vez registrada.
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- ── Cumplimiento ────────────────────────────────────────────────── --}}
                    <h5 class="text-primary mt-4 mb-3">Cumplimiento y Documento</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="consent_legal_basis" class="form-label">Base Legal / Finalidad</label>
                            <input type="text"
                                   class="form-control @error('consent_legal_basis') is-invalid @enderror"
                                   id="consent_legal_basis" name="consent_legal_basis"
                                   value="{{ old('consent_legal_basis', $subject->consent_legal_basis) }}" required>
                            @error('consent_legal_basis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="consent_document" class="form-label">Reemplazar Carta de Consentimiento Firmada</label>
                            <input type="file"
                                   class="form-control @error('consent_document') is-invalid @enderror"
                                   id="consent_document" name="consent_document"
                                   accept="application/pdf,image/*">
                            @if($subject->consent_document_path)
                                <small class="text-success mt-1 d-block">
                                    <i class="ri-attachment-line"></i> Documento de consentimiento actual registrado
                                </small>
                            @endif
                            @error('consent_document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('tenant.projects.show', $subject->project_id) }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSubjectTypeFields() {
        var tipo        = document.getElementById('tipo').value;
        var curpGroup   = document.getElementById('curpGroup');
        var curpInput   = document.getElementById('curp');
        var nameLabel   = document.getElementById('nameLabel');
        var ineGroup    = document.getElementById('ineGroup');
        var usernameGroup = document.getElementById('usernameGroup');
        var tier2Group  = document.getElementById('tier2Group');

        if (tipo === 'persona_fisica') {
            curpGroup.style.display  = 'block';
            ineGroup.style.display   = 'flex';
            tier2Group.style.display = 'block';
            curpInput.setAttribute('required', 'required');
            if (usernameGroup) usernameGroup.style.display = 'block';
            nameLabel.innerText = 'Nombre Completo (Persona Física)';
            document.getElementById('name_or_company').placeholder = 'Ej: Juan Pérez López';
            document.getElementById('rfc').placeholder = 'Ej: PELJ900101XYZ';
        } else {
            curpGroup.style.display  = 'none';
            ineGroup.style.display   = 'none';
            tier2Group.style.display = 'none';
            curpInput.removeAttribute('required');
            curpInput.value = '';
            if (usernameGroup) usernameGroup.style.display = 'none';
            nameLabel.innerText = 'Razón Social / Nombre Legal';
            document.getElementById('name_or_company').placeholder = 'Ej: Aceros de México S.A. de C.V.';
            document.getElementById('rfc').placeholder = 'Ej: AME120304XYZ';
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        toggleSubjectTypeFields();
    });
</script>
@endsection
