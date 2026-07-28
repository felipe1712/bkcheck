@extends('layouts.master')
@section('title') Expediente: {{ $subject->name_or_company }} @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Expediente de Sujeto de Investigación</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">Inicio</a></li>
                    @if($subject->project_id)
                        <li class="breadcrumb-item"><a href="{{ route('tenant.projects.show', $subject->project_id) }}">Proyecto</a></li>
                    @endif
                    <li class="breadcrumb-item active">Expediente</li>
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

@php
    $curpQuery = $queries->firstWhere('source_type', 'curp');
    $curpData = $curpQuery?->result?->processed_data ?? [];
    $rfcQuery = $queries->firstWhere('source_type', 'rfc');
    $csdQuery = $queries->firstWhere('source_type', 'csd');
    $sigerQuery = $queries->firstWhere('source_type', 'siger');
    $satListasQuery = $queries->firstWhere('source_type', 'sat_listas');
    $marcasQuery = $queries->firstWhere('source_type', 'marcas');
    $ineFrenteQuery = $queries->firstWhere('source_type', 'ine_frente');
    $ineReversoQuery = $queries->firstWhere('source_type', 'ine_reverso');
    $sancionesQuery = $queries->firstWhere('source_type', 'sanciones');
    $litigiosQuery = $queries->firstWhere('source_type', 'litigios');
    $identidadDigitalQuery = $queries->firstWhere('source_type', 'identidad_digital');
    $nssQuery = $queries->firstWhere('source_type', 'nss_imss');
    $osintQuery = $queries->firstWhere('source_type', 'presencia_en_linea');

    $hasCompletedQueries = $queries->where('status', 'completed')->isNotEmpty();
    $isProcessing = $queries->whereIn('status', ['pending', 'processing'])->isNotEmpty();

    // Check alerts
    $hasSatAlert = false;
    if ($satListasQuery && $satListasQuery->status === 'completed') {
        $hasSatAlert = !empty($satListasQuery->result?->processed_data['en_lista_69b']);
    }

    $hasSancionesAlert = false;
    if ($sancionesQuery && $sancionesQuery->status === 'completed') {
        $hasSancionesAlert = !empty($sancionesQuery->result?->processed_data['encontrado']);
    }

    $hasLitigiosAlert = false;
    if ($litigiosQuery && $litigiosQuery->status === 'completed') {
        $hasLitigiosAlert = !empty($litigiosQuery->result?->processed_data['tiene_juicios']);
    }
@endphp

@if($hasSatAlert)
<div class="alert alert-danger alert-dismissible fade show border-0 shadow mb-3" role="alert">
    <div class="d-flex align-items-center">
        <div class="flex-shrink-0 me-3">
            <i class="ri-error-warning-fill fs-24 align-middle text-danger"></i>
        </div>
        <div class="flex-grow-1">
            <h5 class="alert-heading text-danger fw-semibold">¡ALERTA DE RIESGO FISCAL CRÍTICO!</h5>
            <p class="mb-0 fs-13">El sujeto ha sido encontrado en las listas del SAT del artículo <strong>69-B (Facturación Simulada - EFOS/EDOS)</strong> con estatus de <strong>{{ $satListasQuery->result?->processed_data['estatus_69b'] ?? 'Presunto' }}</strong>. Revise los detalles abajo.</p>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($hasSancionesAlert)
<div class="alert alert-danger alert-dismissible fade show border-0 shadow mb-3" role="alert">
    <div class="d-flex align-items-center">
        <div class="flex-shrink-0 me-3">
            <i class="ri-shield-user-fill fs-24 align-middle text-danger"></i>
        </div>
        <div class="flex-grow-1">
            <h5 class="alert-heading text-danger fw-semibold">¡ALERTA DE CUMPLIMIENTO / LISTA NEGRA!</h5>
            <p class="mb-0 fs-13">Se detectaron coincidencias del sujeto en <strong>listas de sanciones internacionales (OFAC/Vigilancia) o PEPs</strong>. Se recomienda revisión exhaustiva.</p>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($hasLitigiosAlert)
<div class="alert alert-warning alert-dismissible fade show border-0 shadow mb-3" role="alert">
    <div class="d-flex align-items-center">
        <div class="flex-shrink-0 me-3">
            <i class="ri-folders-fill fs-24 align-middle text-warning"></i>
        </div>
        <div class="flex-grow-1">
            <h5 class="alert-heading text-warning fw-semibold">¡HISTORIAL LEGAL ENCONTRADO!</h5>
            <p class="mb-0 fs-13">Se detectaron <strong>expedientes de juicios o litigios activos/pasados</strong> en juzgados civiles, mercantiles o laborales vinculados al nombre del sujeto.</p>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <!-- Ficha de Datos Horizontal (Ancho completo) -->
    <div class="col-12">
        <div class="card card-profile mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <!-- Columna 1: Perfil y Tipo -->
                    <div class="col-lg-3 border-end text-center text-lg-start mb-3 mb-lg-0">
                        <div class="d-flex flex-column flex-lg-row align-items-center gap-3">
                            <div class="avatar-md">
                                <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24 shadow material-shadow">
                                    {{ $subject->tipo == 'persona_fisica' ? 'PF' : 'PM' }}
                                </span>
                            </div>
                            <div>
                                <h5 class="fs-16 mb-1 text-dark fw-bold">{{ $subject->name_or_company }}</h5>
                                <span class="badge bg-primary-subtle text-primary text-uppercase fs-11">
                                    {{ str_replace('_', ' ', $subject->tipo) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Columna 2: Identificaciones Oficiales -->
                    <div class="col-lg-3 border-end mb-3 mb-lg-0 px-lg-4">
                        <div class="d-flex flex-column gap-2">
                            <div>
                                <span class="text-muted fs-12 uppercase fw-semibold">RFC:</span>
                                <span class="d-block"><code class="fs-13">{{ $subject->rfc }}</code></span>
                            </div>
                            @if($subject->curp)
                            <div>
                                <span class="text-muted fs-12 uppercase fw-semibold">CURP:</span>
                                <span class="d-block"><code class="fs-13">{{ $subject->curp }}</code></span>
                            </div>
                            @endif
                            <div>
                                <span class="text-muted fs-12 uppercase fw-semibold">Domicilio:</span>
                                <span class="d-block text-muted fs-13 text-truncate" style="max-width: 100%" title="{{ $subject->address ?: 'No provisto' }}">
                                    {{ $subject->address ?: 'No provisto' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Columna 3: Consentimiento Legal -->
                    <div class="col-lg-4 border-end mb-3 mb-lg-0 px-lg-4">
                        <div class="row">
                            <div class="col-6">
                                <span class="text-muted fs-12 fw-semibold d-block">Consentimiento:</span>
                                <span class="badge bg-success-subtle text-success fs-11 mt-1">Sí, Expreso</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted fs-12 fw-semibold d-block">Fecha de Firma:</span>
                                <span class="fs-13 text-dark d-block mt-1">{{ $subject->consent_date ? \Carbon\Carbon::parse($subject->consent_date)->format('d/m/Y H:i') : 'N/A' }}</span>
                            </div>
                            <div class="col-12 mt-2">
                                <span class="text-muted fs-12 fw-semibold d-block">Finalidad / Base Legal:</span>
                                <span class="fs-13 text-muted d-block text-truncate" title="{{ $subject->consent_legal_basis }}">{{ $subject->consent_legal_basis }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Columna 4: Acciones Ficha -->
                    <div class="col-lg-2 text-center">
                        <a href="{{ route('tenant.subjects.edit', $subject->id) }}" class="btn btn-soft-primary btn-md w-100 mb-2">
                            <i class="ri-edit-box-line align-middle me-1"></i> Editar Ficha
                        </a>
                        @if($hasCompletedQueries)
                            <a href="{{ route('tenant.subjects.report', $subject->id) }}" target="_blank" class="btn btn-info btn-md w-100">
                                <i class="ri-file-pdf-line align-middle me-1"></i> Reporte PDF
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─────────── SECCIÓN: NIVEL DE INVESTIGACIÓN (TIER SELECTOR) ─────────── --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0" style="background: #141923; color: #fff;">
            <div class="card-header bg-transparent border-bottom border-secondary-subtle d-flex align-items-center justify-content-between py-3" style="border-color: rgba(255,255,255,0.1) !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-shield-star-fill fs-20 text-primary"></i>
                    <h5 class="card-title text-white fw-bold mb-0" style="font-family: 'Rubik', sans-serif;">Nivel de Investigación (TIER)</h5>
                </div>
                <span class="badge bg-primary text-white fs-12 px-3 py-2" id="currentTierBadge">Nivel Actual: TIER {{ $subject->tier_level ?? 1 }}</span>
            </div>
            <div class="card-body p-4">
                <p class="text-white-50 fs-13 mb-3">
                    Selecciona el alcance de la investigación. Las fuentes de verificación y consultas ejecutadas se adaptarán automáticamente al nivel elegido:
                </p>

                <form id="tierLevelForm" action="{{ route('tenant.subjects.update-tier', $subject->id) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        {{-- NIVEL 1 --}}
                        <div class="col-md-6 col-xl-3">
                            <div class="tier-card p-3 rounded-3 border transition-all h-100 cursor-pointer" id="tierCard1"
                                 style="{{ ($subject->tier_level ?? 1) == 1 ? 'background: linear-gradient(135deg, rgba(24, 119, 242, 0.22) 0%, rgba(27, 34, 48, 0.95) 100%); border-color: #1877f2 !important; box-shadow: 0 0 15px rgba(24, 119, 242, 0.35);' : 'background: #1b2230; border-color: rgba(255,255,255,0.12) !important;' }}"
                                 onclick="selectTierLevel(1)">
                                <div class="form-check d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-check-label fw-bold fs-14 text-white cursor-pointer" for="tier1">
                                        <i class="ri-checkbox-circle-fill me-1 text-primary"></i> NIVEL 1
                                    </label>
                                    <input class="form-check-input cursor-pointer" type="radio" name="tier_level" id="tier1" value="1" {{ ($subject->tier_level ?? 1) == 1 ? 'checked' : '' }}>
                                </div>
                                <h6 class="fw-bold fs-13 mb-1" style="color: #00a6ff; font-family: 'Rubik', sans-serif;">Verificación Básica</h6>
                                <p class="fs-11 text-white-50 mb-2">Ideal para personal operativo masivo.</p>
                                <hr class="my-2" style="border-color: rgba(255,255,255,0.15) !important;">
                                <ul class="list-unstyled fs-12 mb-0 text-white" style="line-height: 1.6;">
                                    <li><i class="ri-check-line text-success me-1"></i> CURP / RENAPO</li>
                                    <li><i class="ri-check-line text-success me-1"></i> RFC SAT</li>
                                    <li><i class="ri-check-line text-success me-1"></i> Lista SAT 69/69B</li>
                                    <li><i class="ri-check-line text-success me-1"></i> Listas OFAC y Sanciones</li>
                                    <li><i class="ri-check-line text-success me-1"></i> Biometría y Prueba de Vida</li>
                                </ul>
                            </div>
                        </div>

                        {{-- NIVEL 2 --}}
                        <div class="col-md-6 col-xl-3">
                            <div class="tier-card p-3 rounded-3 border transition-all h-100 cursor-pointer" id="tierCard2"
                                 style="{{ ($subject->tier_level ?? 1) == 2 ? 'background: linear-gradient(135deg, rgba(24, 119, 242, 0.22) 0%, rgba(27, 34, 48, 0.95) 100%); border-color: #1877f2 !important; box-shadow: 0 0 15px rgba(24, 119, 242, 0.35);' : 'background: #1b2230; border-color: rgba(255,255,255,0.12) !important;' }}"
                                 onclick="selectTierLevel(2)">
                                <div class="form-check d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-check-label fw-bold fs-14 text-white cursor-pointer" for="tier2">
                                        <i class="ri-checkbox-circle-fill me-1 text-primary"></i> NIVEL 2
                                    </label>
                                    <input class="form-check-input cursor-pointer" type="radio" name="tier_level" id="tier2" value="2" {{ ($subject->tier_level ?? 1) == 2 ? 'checked' : '' }}>
                                </div>
                                <h6 class="fw-bold fs-13 mb-1" style="color: #00a6ff; font-family: 'Rubik', sans-serif;">Verificación Estándar</h6>
                                <p class="fs-11 text-white-50 mb-2">Personal administrativo y mandos medios.</p>
                                <hr class="my-2" style="border-color: rgba(255,255,255,0.15) !important;">
                                <ul class="list-unstyled fs-12 mb-0 text-white" style="line-height: 1.6;">
                                    <li class="fw-semibold text-primary"><i class="ri-add-line me-1"></i> Todo Nivel 1 más:</li>
                                    <li><i class="ri-check-line text-success me-1"></i> Expedientes Judiciales</li>
                                    <li><i class="ri-check-line text-success me-1"></i> Historial Laboral IMSS / NSS</li>
                                    <li><i class="ri-check-line text-success me-1"></i> Validación completa INE OCR</li>
                                    <li><i class="ri-check-line text-success me-1"></i> Enriquecimiento Digital Email</li>
                                </ul>
                            </div>
                        </div>

                        {{-- NIVEL 3 --}}
                        <div class="col-md-6 col-xl-3">
                            <div class="tier-card p-3 rounded-3 border transition-all h-100 cursor-pointer" id="tierCard3"
                                 style="{{ ($subject->tier_level ?? 1) == 3 ? 'background: linear-gradient(135deg, rgba(24, 119, 242, 0.22) 0%, rgba(27, 34, 48, 0.95) 100%); border-color: #1877f2 !important; box-shadow: 0 0 15px rgba(24, 119, 242, 0.35);' : 'background: #1b2230; border-color: rgba(255,255,255,0.12) !important;' }}"
                                 onclick="selectTierLevel(3)">
                                <div class="form-check d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-check-label fw-bold fs-14 text-white cursor-pointer" for="tier3">
                                        <i class="ri-checkbox-circle-fill me-1 text-primary"></i> NIVEL 3
                                    </label>
                                    <input class="form-check-input cursor-pointer" type="radio" name="tier_level" id="tier3" value="3" {{ ($subject->tier_level ?? 1) == 3 ? 'checked' : '' }}>
                                </div>
                                <h6 class="fw-bold fs-13 mb-1" style="color: #00a6ff; font-family: 'Rubik', sans-serif;">Verificación Ejecutiva</h6>
                                <p class="fs-11 text-white-50 mb-2">Mandos altos y perfiles críticos.</p>
                                <hr class="my-2" style="border-color: rgba(255,255,255,0.15) !important;">
                                <ul class="list-unstyled fs-12 mb-0 text-white" style="line-height: 1.6;">
                                    <li class="fw-semibold text-primary"><i class="ri-add-line me-1"></i> Todo Nivel 2 más:</li>
                                    <li><i class="ri-check-line text-success me-1"></i> OSINT / Redes Sociales</li>
                                    <li><i class="ri-check-line text-success me-1"></i> Análisis Perfil Digital</li>
                                    <li><i class="ri-check-line text-success me-1"></i> IMPI Registro de Marcas</li>
                                    <li><i class="ri-check-line text-success me-1"></i> Validación CSD / CFDI</li>
                                </ul>
                            </div>
                        </div>

                        {{-- NIVEL 4 --}}
                        <div class="col-md-6 col-xl-3">
                            @php $isMoral = $subject->tipo === 'persona_moral'; @endphp
                            <div class="tier-card p-3 rounded-3 border transition-all h-100 {{ $isMoral ? 'cursor-pointer' : 'opacity-50' }}" id="tierCard4"
                                 style="{{ ($subject->tier_level ?? 1) == 4 ? 'background: linear-gradient(135deg, rgba(24, 119, 242, 0.22) 0%, rgba(27, 34, 48, 0.95) 100%); border-color: #1877f2 !important; box-shadow: 0 0 15px rgba(24, 119, 242, 0.35);' : 'background: #1b2230; border-color: rgba(255,255,255,0.12) !important;' }}"
                                 {!! $isMoral ? 'onclick="selectTierLevel(4)"' : '' !!}>
                                <div class="form-check d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-check-label fw-bold fs-14 text-white cursor-pointer" for="tier4">
                                        <i class="ri-building-4-line me-1 text-warning"></i> NIVEL 4
                                    </label>
                                    <input class="form-check-input cursor-pointer" type="radio" name="tier_level" id="tier4" value="4" {{ ($subject->tier_level ?? 1) == 4 ? 'checked' : '' }} {{ !$isMoral ? 'disabled' : '' }}>
                                </div>
                                <h6 class="fw-bold fs-13 text-warning mb-1" style="font-family: 'Rubik', sans-serif;">Verificación Corporativa</h6>
                                <p class="fs-11 text-white-50 mb-2">Due diligence, proveedores, socios.</p>
                                @if(!$isMoral)
                                    <span class="badge bg-warning-subtle text-warning fs-10 mb-2 d-block">Solo para Personas Morales</span>
                                @endif
                                <hr class="my-2" style="border-color: rgba(255,255,255,0.15) !important;">
                                <ul class="list-unstyled fs-12 mb-0 text-white" style="line-height: 1.6;">
                                    <li class="fw-semibold text-warning"><i class="ri-add-line me-1"></i> Todo Nivel 3 más:</li>
                                    <li><i class="ri-check-line text-success me-1"></i> SIGER Registro Público</li>
                                    <li><i class="ri-check-line text-success me-1"></i> Actos Constitutivos</li>
                                    <li><i class="ri-check-line text-success me-1"></i> DENUE INEGI Empresa</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2 mt-4 pt-3 border-top border-secondary-subtle" style="border-color: rgba(255,255,255,0.1) !important;">
                        <button type="button" class="btn btn-outline-light px-3 fw-semibold d-none" id="btnEditTier" onclick="enableTierEdit()">
                            <i class="ri-edit-line me-1"></i> Cambiar selección
                        </button>
                        <button type="button" class="btn btn-primary px-4 fw-semibold shadow" id="btnSaveTier" onclick="saveTierLevel()">
                            <i class="ri-save-3-line me-1"></i> Guardar Nivel de Investigación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ─────────── TARJETA DE ENROLAMIENTO ─────────── --}}
@php
    $enrollStatus = $subject->enrollmentStatus();
    $enrollUrl    = $subject->enrollment_token
        ? url('/enroll/' . $subject->enrollment_token)
        : null;
    $enrollBadge  = match($enrollStatus) {
        'completado'  => ['class' => 'bg-success',          'icon' => 'ri-check-circle-fill',    'label' => 'Completado'],
        'en_proceso'  => ['class' => 'bg-warning text-dark','icon' => 'ri-loader-4-line',        'label' => 'T&C Aceptados — Pendiente fotos'],
        'expirado'    => ['class' => 'bg-danger',           'icon' => 'ri-time-line',            'label' => 'Expirado'],
        'pendiente'   => ['class' => 'bg-primary',          'icon' => 'ri-send-plane-fill',      'label' => 'Pendiente de envío'],
        default       => ['class' => 'bg-secondary',        'icon' => 'ri-link-unlink',          'label' => 'Sin enlace'],
    };
    $selfieQuery = $queries->firstWhere('source_type', 'selfie');
@endphp

<div class="row mb-4">
    <div class="col-12">
        <div class="card border border-dashed border-primary-subtle">
            <div class="card-body p-4">
                <div class="row align-items-center g-3">
                    <!-- Ícono + título -->
                    <div class="col-auto d-none d-md-block">
                        <div class="avatar-md">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-22">
                                <i class="ri-smartphone-line"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="col-md">
                        <h5 class="fw-semibold fs-15 mb-1">
                            <i class="ri-smartphone-line me-2 d-md-none text-primary"></i>
                            Enrolamiento del Investigado
                        </h5>
                        <p class="text-muted fs-13 mb-0">
                            Comparte este enlace con el investigado para que suba su INE y selfie desde su celular (sin necesidad de instalar ninguna aplicación).
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge {{ $enrollBadge['class'] }} fs-11">
                                <i class="{{ $enrollBadge['icon'] }} me-1"></i>{{ $enrollBadge['label'] }}
                            </span>
                            @if($subject->enrollment_expires_at && $enrollStatus === 'pendiente')
                                <span class="badge bg-light text-muted fs-11">
                                    <i class="ri-timer-line me-1"></i>
                                    Expira {{ \Carbon\Carbon::parse($subject->enrollment_expires_at)->diffForHumans() }}
                                    ({{ \Carbon\Carbon::parse($subject->enrollment_expires_at)->format('d/m/Y H:i') }})
                                </span>
                            @endif
                            @if($subject->enrollment_tc_accepted_at)
                                <span class="badge bg-info-subtle text-info fs-11">
                                    <i class="ri-file-text-line me-1"></i>
                                    T&C aceptados: {{ \Carbon\Carbon::parse($subject->enrollment_tc_accepted_at)->format('d/m/Y H:i') }}
                                </span>
                            @endif
                            @if($subject->enrollment_completed_at)
                                <span class="badge bg-success-subtle text-success fs-11">
                                    <i class="ri-calendar-check-line me-1"></i>
                                    Completado: {{ \Carbon\Carbon::parse($subject->enrollment_completed_at)->format('d/m/Y H:i') }}
                                </span>
                            @endif
                            @if($subject->selfie_path || ($selfieQuery && $selfieQuery->status === 'completed'))
                                <span class="badge bg-success-subtle text-success fs-11">
                                    <i class="ri-user-smile-line me-1"></i> Selfie recibida
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- URL + Acciones -->
                    <div class="col-md-auto">
                        @if($enrollUrl && in_array($enrollStatus, ['pendiente', 'en_proceso']))
                            <div class="input-group input-group-sm mb-2" style="max-width:340px">
                                <input type="text" class="form-control form-control-sm bg-light border-end-0 fs-12"
                                    id="enrollUrlInput"
                                    value="{{ $enrollUrl }}"
                                    readonly>
                                <button class="btn btn-light border border-start-0"
                                    type="button"
                                    id="btnCopyEnroll"
                                    title="Copiar enlace"
                                    onclick="copyEnrollUrl()">
                                    <i class="ri-clipboard-line"></i>
                                </button>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="https://wa.me/?text={{ urlencode('Hola ' . $subject->name_or_company . ', completa tu verificación de identidad aquí: ' . $enrollUrl . ' (válido 24h)') }}"
                                   target="_blank"
                                   class="btn btn-success btn-sm">
                                    <i class="ri-whatsapp-line me-1"></i> WhatsApp
                                </a>
                                <a href="sms:?body={{ urlencode('Verifica tu identidad en: ' . $enrollUrl) }}"
                                   class="btn btn-soft-info btn-sm">
                                    <i class="ri-message-2-line me-1"></i> SMS
                                </a>
                            </div>
                        @elseif($enrollStatus === 'completado')
                            <div class="text-success fw-semibold fs-13">
                                <i class="ri-check-double-line me-1 fs-16"></i>
                                El investigado completó el proceso.<br>
                                <span class="text-muted fs-12 fw-normal">Las imágenes están siendo procesadas.</span>
                            </div>
                        @endif

                        <!-- Botón regenerar (cuando expiró o ya completó) -->
                        @if(in_array($enrollStatus, ['expirado', 'completado', 'sin_token']))
                            <form action="{{ route('tenant.subjects.regenerate-enrollment', $subject->id) }}" method="POST" class="mt-2">
                                @csrf
                                <button type="submit" class="btn btn-soft-warning btn-sm"
                                    onclick="return confirm('¿Generar un nuevo enlace? El anterior quedará inactivo.')">
                                    <i class="ri-refresh-line me-1"></i>
                                    @if($enrollStatus === 'expirado') Regenerar enlace
                                    @elseif($enrollStatus === 'completado') Nuevo proceso de enrolamiento
                                    @else Generar enlace de enrolamiento
                                    @endif
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Fuentes de Verificación (Renglones Colapsables / Acordeón) -->
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0 align-items-center d-flex pb-2">
                <h5 class="card-title mb-0 flex-grow-1 text-dark fw-bold">Fuentes de Verificación de Antecedentes</h5>
                <div class="flex-shrink-0 d-flex gap-2 align-items-center">
                    {{-- Botón re-ejecutar todo (siempre visible) --}}
                    <form action="{{ route('tenant.subjects.investigate', $subject->id) }}" method="POST" id="startInvestigationForm">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-md" id="runInvestigationBtn" {{ $isProcessing ? 'disabled' : '' }}>
                            @if($isProcessing)
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Investigando...
                            @elseif($queries->isEmpty())
                                <i class="ri-play-circle-line align-bottom me-1"></i> Iniciar Investigación
                            @else
                                <i class="ri-refresh-line align-bottom me-1"></i> Re-ejecutar Todo
                            @endif
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card-body">
                <div class="accordion accordion-border-box" id="sourcesAccordion">

                    {{-- SECCIÓN 1: NIVEL 1 — VERIFICACIÓN BÁSICA --}}
                    <div class="tier-section" id="tierSection1">
                        <div class="alert alert-primary border-0 d-flex align-items-center mb-3 shadow-sm py-2 px-3">
                            <i class="ri-shield-check-fill fs-18 me-2"></i>
                            <span class="fw-bold fs-13 flex-grow-1 text-uppercase">NIVEL 1 — Verificación Básica (Identidad, Listas SAT 69/69B y Sanciones/OFAC)</span>
                        </div>

                    <!-- 1. VALIDACIÓN CURP / RENAPO -->
                    @if($subject->tipo === 'persona_fisica')
                    <div class="accordion-item shadow-sm border mb-3">
                        <h2 class="accordion-header" id="headingCurp">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCurp" aria-expanded="false" aria-controls="collapseCurp">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div class="fw-semibold text-dark fs-14">
                                        <i class="ri-fingerprint-line text-primary me-2 align-middle fs-18"></i> Validación CURP / RENAPO
                                    </div>
                                    <div>
                                        @if(!$curpQuery)
                                            <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                        @elseif($curpQuery->status === 'pending' || $curpQuery->status === 'processing')
                                            <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando</span>
                                        @elseif($curpQuery->status === 'failed')
                                            <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                        @elseif($curpQuery->status === 'completed')
                                            @if(!empty($curpData['valida']))
                                                <span class="badge bg-success text-white py-1 px-2">✓ CURP Válida</span>
                                            @else
                                                <span class="badge bg-danger text-white py-1 px-2">✗ CURP Inválida</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapseCurp" class="accordion-collapse collapse" aria-labelledby="headingCurp" data-bs-parent="#sourcesAccordion">
                            <div class="accordion-body">
                                @if(!$curpQuery)
                                    <div class="text-center py-3">
                                        <p class="text-muted mb-2">La consulta de validación de este CURP ante RENAPO aún no se ha iniciado.</p>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'curp']) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                                <i class="ri-play-circle-line me-1"></i> Consultar Fuente
                                            </button>
                                        </form>
                                    </div>
                                @elseif($curpQuery->status === 'pending' || $curpQuery->status === 'processing')
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                        <span>Estamos validando la CURP ante RENAPO. Por favor espere...</span>
                                    </div>
                                @elseif($curpQuery->status === 'failed')
                                    <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center mb-0">
                                        <span><strong>Error:</strong> {{ $curpQuery->error_message }}</span>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'curp']) }}" method="POST" class="ms-3">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>Re-Consultar</button>
                                        </form>
                                    </div>
                                @elseif($curpQuery->status === 'completed')
                                    @php $cData = $curpQuery->result?->processed_data ?? []; @endphp
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle mb-0">
                                            <tbody>
                                                <tr>
                                                    <td class="fw-semibold bg-light" style="width: 30%">CURP Oficial:</td>
                                                    <td><code>{{ $cData['curp'] ?? $subject->curp }}</code></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold bg-light">Estatus RENAPO:</td>
                                                    <td><span class="badge bg-success-subtle text-success fs-12">{{ $cData['estatus_curp'] ?? 'AN (Activo)' }}</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold bg-light">Nombre Completo:</td>
                                                    <td class="fw-semibold text-dark">{{ trim(($cData['nombre'] ?? '') . ' ' . ($cData['primer_apellido'] ?? '') . ' ' . ($cData['segundo_apellido'] ?? '')) ?: 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold bg-light">Fecha de Nacimiento:</td>
                                                    <td>{{ $cData['fecha_nacimiento'] ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold bg-light">Sexo:</td>
                                                    <td>{{ $cData['sexo'] ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold bg-light">Entidad de Nacimiento:</td>
                                                    <td>{{ $cData['estado_nacimiento'] ?? 'N/A' }}</td>
                                                </tr>
                                                @if(!empty($cData['nacionalidad']))
                                                <tr>
                                                    <td class="fw-semibold bg-light">Nacionalidad:</td>
                                                    <td>{{ $cData['nacionalidad'] }}</td>
                                                </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- 2. VALIDACIÓN RFC -->
                    <div class="accordion-item shadow-sm border mb-3">
                        <h2 class="accordion-header" id="headingRfc">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRfc" aria-expanded="false" aria-controls="collapseRfc">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div class="fw-semibold text-dark fs-14">
                                        <i class="ri-government-fill text-primary me-2 align-middle fs-18"></i> Validación RFC (SAT)
                                    </div>
                                    <div>
                                        @if(!$rfcQuery)
                                            <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                        @elseif($rfcQuery->status === 'pending' || $rfcQuery->status === 'processing')
                                            <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando</span>
                                        @elseif($rfcQuery->status === 'failed')
                                            <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                        @elseif($rfcQuery->status === 'completed')
                                            @if(!empty($rfcQuery->result?->processed_data['valido']))
                                                <span class="badge bg-success text-white py-1 px-2">Completed - RFC Válido</span>
                                            @else
                                                <span class="badge bg-danger text-white py-1 px-2">Completed - RFC Inválido</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapseRfc" class="accordion-collapse collapse" aria-labelledby="headingRfc" data-bs-parent="#sourcesAccordion">
                            <div class="accordion-body">
                                @if(!$rfcQuery)
                                    <div class="text-center py-3">
                                        <p class="text-muted mb-2">La consulta de validación de este RFC ante el SAT aún no se ha iniciado.</p>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'rfc']) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                                <i class="ri-play-circle-line me-1"></i> Consultar Fuente
                                            </button>
                                        </form>
                                    </div>
                                @elseif($rfcQuery->status === 'pending' || $rfcQuery->status === 'processing')
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                        <span>Estamos validando los datos ante el SAT. Por favor espere...</span>
                                    </div>
                                @elseif($rfcQuery->status === 'failed')
                                    <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center mb-0">
                                        <span><strong>Error:</strong> {{ $rfcQuery->error_message }}</span>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'rfc']) }}" method="POST" class="ms-3">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>Re-Consultar</button>
                                        </form>
                                    </div>
                                @elseif($rfcQuery->status === 'completed')
                                    @php $rfcData = $rfcQuery->result?->processed_data ?? []; @endphp
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle mb-0">
                                            <tbody>
                                                <tr>
                                                    <td class="fw-semibold bg-light" style="width: 30%">RFC Oficial:</td>
                                                    <td><code>{{ $rfcData['rfc'] ?? 'N/A' }}</code></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold bg-light">Estatus Vigencia:</td>
                                                    <td>
                                                        @if($rfcData['valido'] ?? false)
                                                            <span class="badge bg-success-subtle text-success">Sí, Vigente</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger">No Registrado / No Válido</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold bg-light">Situación SAT:</td>
                                                    <td><span class="badge bg-info-subtle text-info">{{ $rfcData['situacion'] ?? 'ACTIVO' }}</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold bg-light">Razón Social:</td>
                                                    <td class="fw-semibold text-dark">{{ $rfcData['razon_social'] ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold bg-light">Tipo de Persona:</td>
                                                    <td>{{ $rfcData['tipo_persona'] ?? 'N/A' }}</td>
                                                </tr>
                                                @if(isset($rfcData['curp']) && $rfcData['curp'])
                                                <tr>
                                                    <td class="fw-semibold bg-light">CURP Cruzado:</td>
                                                    <td><code>{{ $rfcData['curp'] }}</code></td>
                                                </tr>
                                                @endif
                                            </tbody>
                                        </table>
                              @endif
                            </div>
                        </div>
                    </div>

                    <!-- 3. LISTAS SAT 69/B -->
                    <div class="accordion-item shadow-sm border mb-3">
                        <h2 class="accordion-header" id="headingSatListas">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSatListas" aria-expanded="false" aria-controls="collapseSatListas">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div class="fw-semibold text-dark fs-14">
                                        <i class="ri-file-warning-fill text-primary me-2 align-middle fs-18"></i> Listas SAT 69 y 69-B (Cumplimiento Fiscal)
                                    </div>
                                    <div>
                                        @if(!$satListasQuery)
                                            <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                        @elseif($satListasQuery->status === 'pending' || $satListasQuery->status === 'processing')
                                            <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando</span>
                                        @elseif($satListasQuery->status === 'failed')
                                            <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                        @elseif($satListasQuery->status === 'completed')
                                            @if($hasSatAlert)
                                                <span class="badge bg-danger text-white py-1 px-2"><i class="ri-error-warning-fill me-1"></i> RIESGO DETECTADO (69-B)</span>
                                            @else
                                                <span class="badge bg-success text-white py-1 px-2">Completed - Sin Incidencias</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapseSatListas" class="accordion-collapse collapse" aria-labelledby="headingSatListas" data-bs-parent="#sourcesAccordion">
                            <div class="accordion-body">
                                @if(!$satListasQuery)
                                    <div class="text-center py-3">
                                        <p class="text-muted mb-2">La revisión de listados negros de contribuyentes no localizados o simuladores ante el SAT aún no se ha iniciado.</p>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'sat_listas']) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                                <i class="ri-play-circle-line me-1"></i> Consultar Fuente
                                            </button>
                                        </form>
                                    </div>
                                @elseif($satListasQuery->status === 'pending' || $satListasQuery->status === 'processing')
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                        <span>Buscando reportes negativos en boletines oficiales del SAT. Espere...</span>
                                    </div>
                                @elseif($satListasQuery->status === 'failed')
                                    <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center mb-0">
                                        <span><strong>Error:</strong> {{ $satListasQuery->error_message }}</span>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'sat_listas']) }}" method="POST" class="ms-3">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>Re-Consultar</button>
                                        </form>
                                    </div>
                                @elseif($satListasQuery->status === 'completed')
                                    @php $listData = $satListasQuery->result?->processed_data ?? []; @endphp
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="card border mb-0">
                                                <div class="card-body">
                                                    <h6 class="text-muted mb-2 text-uppercase fs-11">Lista 69 (Exceptuados/Incumplidos)</h6>
                                                    @if($listData['en_lista_69'] ?? false)
                                                        <span class="badge bg-danger-subtle text-danger fs-12 p-2 w-100 text-center"><i class="ri-error-warning-line me-1"></i> Contribuyente Exceptuado</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success fs-12 p-2 w-100 text-center"><i class="ri-checkbox-circle-line me-1"></i> Sin Incidencias Registradas</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card border mb-0">
                                                <div class="card-body">
                                                    <h6 class="text-muted mb-2 text-uppercase fs-11">Lista 69-B (EFOS - Simulación de Operaciones)</h6>
                                                    @if($listData['en_lista_69b'] ?? false)
                                                        <span class="badge bg-danger-subtle text-danger fs-12 p-2 w-100 text-center"><i class="ri-alert-line me-1"></i> Boletinado Simulador</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success fs-12 p-2 w-100 text-center"><i class="ri-checkbox-circle-line me-1"></i> Sin Incidencias Registradas</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($listData['en_lista_69b'] ?? false)
                                    @php
                                        $estatusVal = $listData['estatus_69b'] ?? 'Presunto';
                                        $sitDetalle = \App\Services\BackgroundCheck\Nufi\NufiSatListasConnector::getSituacionDetalle($estatusVal);
                                    @endphp
                                    <div class="card border border-danger border-dashed mb-0 mt-3">
                                        <div class="card-header bg-danger-subtle text-danger pb-2 d-flex justify-content-between align-items-center">
                                            <h6 class="card-title mb-0 text-danger fw-semibold"><i class="ri-error-warning-fill me-1 align-middle"></i> Detalles del Boletín Oficial 69-B</h6>
                                            <span class="badge bg-{{ $sitDetalle['badge_class'] ?? 'danger' }} fs-11 px-2 py-1">{{ strtoupper($estatusVal) }}</span>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm mb-3">
                                                <tbody>
                                                    <tr>
                                                        <td class="fw-semibold bg-light" style="width: 30%">Estatus Legal SAT:</td>
                                                        <td><span class="badge bg-{{ $sitDetalle['badge_class'] ?? 'warning' }} px-2 py-1 fs-12">{{ strtoupper($estatusVal) }}</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold bg-light">Número de Oficio:</td>
                                                        <td><code>{{ $listData['oficio_oficial'] ?? 'N/A' }}</code></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold bg-light">Publicación DOF:</td>
                                                        <td>{{ $listData['fecha_publicacion'] ?? 'N/A' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div class="alert alert-warning-subtle text-dark border-0 fs-12 mb-0">
                                                <i class="ri-information-line me-1 fw-bold text-primary"></i> <strong>Explicación Legal del Estatus:</strong><br>
                                                {{ $listData['situacion_descripcion'] ?? $sitDetalle['descripcion'] }}
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>



                    <!-- 5. LISTAS DE SANCIONES Y PEPS -->
                    <div class="accordion-item shadow-sm border mb-3">
                        <h2 class="accordion-header" id="headingSanciones">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSanciones" aria-expanded="false" aria-controls="collapseSanciones">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div class="fw-semibold text-dark fs-14">
                                        <i class="ri-global-line text-primary me-2 align-middle fs-18"></i> Listas Negras Internacionales (AML / PEPs / OFAC)
                                    </div>
                                    <div>
                                        @if(!$sancionesQuery)
                                            <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                        @elseif($sancionesQuery->status === 'pending' || $sancionesQuery->status === 'processing')
                                            <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando</span>
                                        @elseif($sancionesQuery->status === 'failed')
                                            <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                        @elseif($sancionesQuery->status === 'completed')
                                            @if($hasSancionesAlert)
                                                <span class="badge bg-danger text-white py-1 px-2 blink-effect"><i class="ri-alert-fill me-1"></i> RIESGO DE CUMPLIMIENTO (PEPs/OFAC)</span>
                                            @else
                                                <span class="badge bg-success text-white py-1 px-2">Completed - Sin Coincidencias</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapseSanciones" class="accordion-collapse collapse" aria-labelledby="headingSanciones" data-bs-parent="#sourcesAccordion">
                            <div class="accordion-body">
                                @if(!$sancionesQuery)
                                    <div class="text-center py-3">
                                        <p class="text-muted mb-2">La búsqueda en listas de sanciones, terrorismo y PEPs internacionales aún no se ha iniciado.</p>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'sanciones']) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                                <i class="ri-play-circle-line me-1"></i> Consultar Fuente
                                            </button>
                                        </form>
                                    </div>
                                @elseif($sancionesQuery->status === 'pending' || $sancionesQuery->status === 'processing')
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                        <span>Buscando en bases de cumplimiento nacionales e internacionales. Espere...</span>
                                    </div>
                                @elseif($sancionesQuery->status === 'failed')
                                    <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center mb-0">
                                        <span><strong>Error:</strong> {{ $sancionesQuery->error_message }}</span>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'sanciones']) }}" method="POST" class="ms-3">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>Re-Consultar</button>
                                        </form>
                                    </div>
                                @elseif($sancionesQuery->status === 'completed')
                                    @php $sancData = $sancionesQuery->result?->processed_data ?? []; @endphp
                                    @if(empty($sancData['hits'] ?? []))
                                        <div class="alert alert-success border-0 mb-0">
                                            <h6 class="alert-heading text-success fw-semibold"><i class="ri-checkbox-circle-line me-1"></i> Sin Reportes</h6>
                                            <p class="mb-0">No se detectaron coincidencias en listas de sanciones de la OFAC, Interpol, ONU, ni en listados oficiales de Personas Expuestas Políticamente (PEPs) en México.</p>
                                        </div>
                                    @else
                                        <div class="alert alert-danger border-0 mb-3">
                                            <h6 class="alert-heading text-danger fw-semibold"><i class="ri-error-warning-fill me-1"></i> Coincidencia Detectada</h6>
                                            <p class="mb-0">Se han localizado registros relacionados con el nombre del sujeto en las siguientes listas de vigilancia y cumplimiento:</p>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-striped align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Lista</th>
                                                        <th>Nombre Detectado</th>
                                                        <th>Entidad / País</th>
                                                        <th>Tipo</th>
                                                        <th>Fecha Publicación</th>
                                                        <th>Detalles / Comentarios</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($sancData['hits'] as $hit)
                                                    <tr>
                                                        <td class="fw-semibold text-danger">{{ $hit['lista'] ?? 'N/A' }}</td>
                                                        <td class="fw-medium text-dark">{{ $hit['nombre_encontrado'] ?? 'N/A' }}</td>
                                                        <td>{{ $hit['entidad_pais'] ?? 'N/A' }}</td>
                                                        <td><span class="badge bg-danger-subtle text-danger">{{ $hit['tipo_lista'] ?? 'Sanción' }}</span></td>
                                                        <td>{{ $hit['fecha_publicacion'] ?? 'N/A' }}</td>
                                                        <td class="text-muted fs-12">{{ $hit['comentarios'] ?? '' }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- 5. BIOMETRÍA Y PRUEBA DE VIDA (LIVENESS / SELFIE) -->
                    @if($subject->tipo === 'persona_fisica')
                    <div class="accordion-item shadow-sm border mb-3">
                        <h2 class="accordion-header" id="headingSelfie">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSelfie" aria-expanded="false" aria-controls="collapseSelfie">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div class="fw-semibold text-dark fs-14">
                                        <i class="ri-user-smile-fill text-primary me-2 align-middle fs-18"></i> Biometría y Prueba de Vida (Liveness / Selfie)
                                    </div>
                                    <div>
                                        @if(!$selfieQuery)
                                            <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                        @elseif($selfieQuery->status === 'pending' || $selfieQuery->status === 'processing')
                                            <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando</span>
                                        @elseif($selfieQuery->status === 'failed')
                                            <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                        @elseif($selfieQuery->status === 'completed')
                                            @php $sData = $selfieQuery->result?->processed_data ?? []; @endphp
                                            @if($sData['aceptado'] ?? false)
                                                <span class="badge bg-success text-white py-1 px-2"><i class="ri-checkbox-circle-fill me-1"></i> Aprobado — Prueba de Vida Válida</span>
                                            @else
                                                <span class="badge bg-danger text-white py-1 px-2"><i class="ri-error-warning-fill me-1"></i> No Aprobado</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapseSelfie" class="accordion-collapse collapse" aria-labelledby="headingSelfie" data-bs-parent="#sourcesAccordion">
                            <div class="accordion-body">
                                {{-- Imagen Selfie Cargada --}}
                                <div class="card border bg-light-subtle mb-3">
                                    <div class="card-body d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ri-user-unfollow-line fs-24 text-primary"></i>
                                            <div>
                                                <span class="fs-12 fw-bold text-dark d-block">Selfie Capturada</span>
                                                @if($subject->selfie_path)
                                                    <span class="badge bg-success-subtle text-success fs-10"><i class="ri-checkbox-circle-line me-1"></i>Imagen disponible</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning fs-10"><i class="ri-alert-line me-1"></i>Sin selfie</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if($subject->selfie_path)
                                            <button type="button" class="btn btn-sm btn-outline-primary fs-11" onclick="openDocModal('{{ route('tenant.subjects.document', [$subject->id, 'selfie']) }}', 'Selfie de Verificación Biométrica')">
                                                <i class="ri-eye-line me-1"></i>Ver Selfie
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                @if(!$selfieQuery)
                                    <div class="text-center py-3">
                                        <p class="text-muted mb-2">La prueba de vida biométrica (Liveness Session) aún no se ha ejecutado.</p>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'selfie']) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                                <i class="ri-play-circle-line me-1"></i> Consultar Fuente
                                            </button>
                                        </form>
                                    </div>
                                @elseif($selfieQuery->status === 'pending' || $selfieQuery->status === 'processing')
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                        <span>Creando sesión y verificando estatus biométrico en NuFi...</span>
                                    </div>
                                @elseif($selfieQuery->status === 'failed')
                                    <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center mb-0">
                                        <span><strong>Error:</strong> {{ $selfieQuery->error_message }}</span>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'selfie']) }}" method="POST" class="ms-3">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>Re-Consultar</button>
                                        </form>
                                    </div>
                                @elseif($selfieQuery->status === 'completed')
                                    @php $sData = $selfieQuery->result?->processed_data ?? []; @endphp
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-white h-100">
                                                <h6 class="fw-bold text-dark fs-12 mb-2 border-bottom pb-2">Dictamen Biométrico</h6>
                                                <div class="mb-2">
                                                    <strong>Resultado:</strong>
                                                    @if($sData['aceptado'] ?? false)
                                                        <span class="badge bg-success-subtle text-success fs-12 ms-1">PRUEBA DE VIDA APROBADA</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger fs-12 ms-1">NO APROBADA / NO COINCIDE</span>
                                                    @endif
                                                </div>
                                                <div class="mb-1 fs-12">
                                                    <strong>ID de Validación NuFi:</strong> <code>{{ $sData['id_validacion'] ?? 'N/A' }}</code>
                                                </div>
                                                <div class="mb-1 fs-12">
                                                    <strong>Puntaje / Rango:</strong> <span class="badge bg-info-subtle text-info">{{ $sData['rango'] ?? 0 }}%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-white h-100">
                                                <h6 class="fw-bold text-dark fs-12 mb-2 border-bottom pb-2">Auditoría / Bitácora de Validación</h6>
                                                @if(!empty($sData['auditoria']))
                                                    <ul class="list-unstyled mb-0 fs-12">
                                                        @foreach($sData['auditoria'] as $auditItem)
                                                            <li class="mb-1 text-muted"><i class="ri-checkbox-circle-line text-success me-1"></i> {{ $auditItem }}</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <p class="text-muted fs-12 mb-0">Verificación procesada correctamente.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                    </div> {{-- /tierSection1 --}}

                    {{-- SECCIÓN 2: NIVEL 2 — VERIFICACIÓN ESTÁNDAR --}}
                    <div class="tier-section" id="tierSection2">
                        <div class="alert alert-info border-0 d-flex align-items-center mb-3 shadow-sm py-2 px-3 mt-4">
                            <i class="ri-user-search-fill fs-18 me-2"></i>
                            <span class="fw-bold fs-13 flex-grow-1 text-uppercase">NIVEL 2 — Verificación Estándar (INE OCR, Judiciales y Historial Laboral)</span>
                        </div>

                    <!-- 1. IDENTIFICACIÓN INE (FRENTE Y REVERSO) -->
                    @if($subject->tipo === 'persona_fisica')
                    <div class="accordion-item shadow-sm border mb-3">
                        <h2 class="accordion-header" id="headingIne">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIne" aria-expanded="false" aria-controls="collapseIne">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div class="fw-semibold text-dark fs-14">
                                        <i class="ri-id-card-fill text-primary me-2 align-middle fs-18"></i> Identificación INE (Frente y Reverso - OCR)
                                    </div>
                                    <div>
                                        @php
                                            $frenteDone = $ineFrenteQuery && $ineFrenteQuery->status === 'completed';
                                            $reversoDone = $ineReversoQuery && $ineReversoQuery->status === 'completed';
                                            $ineProc = ($ineFrenteQuery && in_array($ineFrenteQuery->status, ['pending', 'processing'])) || ($ineReversoQuery && in_array($ineReversoQuery->status, ['pending', 'processing']));
                                            $ineFail = ($ineFrenteQuery && $ineFrenteQuery->status === 'failed') || ($ineReversoQuery && $ineReversoQuery->status === 'failed');
                                        @endphp
                                        @if($frenteDone && $reversoDone)
                                            <span class="badge bg-success text-white py-1 px-2">✓ INE Validada (Frente y Reverso)</span>
                                        @elseif($frenteDone || $reversoDone)
                                            <span class="badge bg-info text-white py-1 px-2">Validación Parcial ({{ $frenteDone ? 'Frente' : 'Reverso' }})</span>
                                        @elseif($ineProc)
                                            <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando OCR</span>
                                        @elseif($ineFail)
                                            <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapseIne" class="accordion-collapse collapse" aria-labelledby="headingIne" data-bs-parent="#sourcesAccordion">
                            <div class="accordion-body">

                                {{-- Documentos del Enrolamiento --}}
                                <div class="card border bg-light-subtle mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-dark fs-13 mb-3">
                                            <i class="ri-image-line text-primary me-1"></i> Imágenes de la Credencial (Cargadas en Enrolamiento)
                                        </h6>
                                        <div class="row g-3">
                                            {{-- Imagen Frente --}}
                                            <div class="col-md-6">
                                                <div class="p-3 bg-white rounded border d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="ri-id-card-line fs-24 text-primary"></i>
                                                        <div>
                                                            <span class="fs-12 fw-bold text-dark d-block">INE Frente</span>
                                                            @if($subject->ine_front_path)
                                                                <span class="badge bg-success-subtle text-success fs-10"><i class="ri-checkbox-circle-line me-1"></i>Imagen disponible</span>
                                                            @else
                                                                <span class="badge bg-warning-subtle text-warning fs-10"><i class="ri-alert-line me-1"></i>Sin imagen</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if($subject->ine_front_path)
                                                        <button type="button" class="btn btn-sm btn-outline-primary fs-11" onclick="openDocModal('{{ route('tenant.subjects.document', [$subject->id, 'ine_front']) }}', 'INE Frente — Documento Vinculado')">
                                                            <i class="ri-eye-line me-1"></i>Ver Imagen
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                            {{-- Imagen Reverso --}}
                                            <div class="col-md-6">
                                                <div class="p-3 bg-white rounded border d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="ri-id-card-line fs-24 text-primary"></i>
                                                        <div>
                                                            <span class="fs-12 fw-bold text-dark d-block">INE Reverso</span>
                                                            @if($subject->ine_back_path)
                                                                <span class="badge bg-success-subtle text-success fs-10"><i class="ri-checkbox-circle-line me-1"></i>Imagen disponible</span>
                                                            @else
                                                                <span class="badge bg-warning-subtle text-warning fs-10"><i class="ri-alert-line me-1"></i>Sin imagen</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if($subject->ine_back_path)
                                                        <button type="button" class="btn btn-sm btn-outline-primary fs-11" onclick="openDocModal('{{ route('tenant.subjects.document', [$subject->id, 'ine_back']) }}', 'INE Reverso — Documento Vinculado')">
                                                            <i class="ri-eye-line me-1"></i>Ver Imagen
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Botones de Ejecución OCR --}}
                                <div class="d-flex flex-wrap gap-2 mb-4">
                                    <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'ine_frente']) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary px-3" {{ $isProcessing || empty($subject->ine_front_path) ? 'disabled' : '' }}>
                                            <i class="ri-play-circle-line me-1"></i> {{ $ineFrenteQuery ? 'Re-Consultar OCR Frente' : 'Consultar OCR Frente' }}
                                        </button>
                                    </form>

                                    <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'ine_reverso']) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary px-3" {{ $isProcessing || empty($subject->ine_back_path) ? 'disabled' : '' }}>
                                            <i class="ri-play-circle-line me-1"></i> {{ $ineReversoQuery ? 'Re-Consultar OCR Reverso' : 'Consultar OCR Reverso' }}
                                        </button>
                                    </form>
                                </div>

                                {{-- Resultados OCR Frente y Reverso --}}
                                <div class="row g-3">
                                    {{-- RESULTADOS FRENTE --}}
                                    <div class="col-lg-7">
                                        <div class="border rounded p-3 bg-white h-100">
                                            <h6 class="fw-bold text-dark fs-13 mb-3 border-bottom pb-2">
                                                <i class="ri-user-line text-primary me-1"></i> Verificación INE frente
                                            </h6>
                                            @if(!$ineFrenteQuery)
                                                <p class="text-muted fs-12 mb-0">No se ha ejecutado la consulta del frente del INE.</p>
                                            @elseif(in_array($ineFrenteQuery->status, ['pending', 'processing']))
                                                <div class="text-center py-3">
                                                    <div class="spinner-border text-primary spinner-border-sm me-2"></div>
                                                    <span class="fs-12 text-muted">Procesando OCR Frente...</span>
                                                </div>
                                            @elseif($ineFrenteQuery->status === 'failed')
                                                <div class="alert alert-danger fs-12 py-2 mb-0">{{ $ineFrenteQuery->error_message }}</div>
                                            @elseif($ineFrenteQuery->status === 'completed')
                                                @php $fData = $ineFrenteQuery->result?->processed_data ?? []; @endphp
                                                <table class="table table-bordered table-sm align-middle fs-12 mb-0">
                                                    <tbody>
                                                        <tr>
                                                            <td class="fw-semibold bg-light" style="width: 40%">Nombre Completo:</td>
                                                            <td class="fw-bold text-dark">{{ trim(($fData['nombre'] ?? '') . ' ' . ($fData['apellido_paterno'] ?? '') . ' ' . ($fData['apellido_materno'] ?? '')) ?: 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-semibold bg-light">CURP Extraído:</td>
                                                            <td><code>{{ $fData['curp'] ?? 'N/A' }}</code></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-semibold bg-light">Clave de Elector:</td>
                                                            <td><code>{{ $fData['clave_elector'] ?? 'N/A' }}</code></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-semibold bg-light">Número de Emisión:</td>
                                                            <td><code>{{ $fData['numero_emision'] ?? 'N/A' }}</code></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-semibold bg-light">Sección Electoral:</td>
                                                            <td>{{ $fData['seccion'] ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-semibold bg-light">Fecha de Nacimiento:</td>
                                                            <td>{{ $fData['fecha_nacimiento'] ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-semibold bg-light">Vigencia:</td>
                                                            <td><span class="badge bg-success-subtle text-success fs-11">{{ $fData['vigencia'] ?? 'N/A' }}</span></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- RESULTADOS REVERSO --}}
                                    <div class="col-lg-5">
                                        <div class="border rounded p-3 bg-white h-100">
                                            <h6 class="fw-bold text-dark fs-13 mb-3 border-bottom pb-2">
                                                <i class="ri-qr-code-line text-primary me-1"></i> Verificación INE reverso
                                            </h6>
                                            @if(!$ineReversoQuery)
                                                <p class="text-muted fs-12 mb-0">No se ha ejecutado la consulta del reverso del INE.</p>
                                            @elseif(in_array($ineReversoQuery->status, ['pending', 'processing']))
                                                <div class="text-center py-3">
                                                    <div class="spinner-border text-primary spinner-border-sm me-2"></div>
                                                    <span class="fs-12 text-muted">Procesando OCR Reverso...</span>
                                                </div>
                                            @elseif($ineReversoQuery->status === 'failed')
                                                <div class="alert alert-danger fs-12 py-2 mb-0">{{ $ineReversoQuery->error_message }}</div>
                                            @elseif($ineReversoQuery->status === 'completed')
                                                @php $rData = $ineReversoQuery->result?->processed_data ?? []; @endphp
                                                <table class="table table-bordered table-sm align-middle fs-12 mb-0">
                                                    <tbody>
                                                        <tr>
                                                            <td class="fw-semibold bg-light" style="width: 45%">CIC:</td>
                                                            <td><code>{{ $rData['cic'] ?? $rData['numero_identificador'] ?? 'N/A' }}</code></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-semibold bg-light">Código OCR / MRZ:</td>
                                                            <td><code class="text-break">{{ $rData['codigo_ocr'] ?? 'N/A' }}</code></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Subir / Reemplazar Imágenes --}}
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <div class="card border border-dashed p-3 mb-0 bg-light-subtle">
                                            <h6 class="fs-12 text-uppercase fw-semibold mb-2 text-primary"><i class="ri-upload-cloud-2-line me-1"></i> Reemplazar Imagen Frente</h6>
                                            <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'ine_frente']) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="input-group input-group-sm">
                                                    <input type="file" name="ine_front" class="form-control" accept="image/*" required>
                                                    <button type="submit" class="btn btn-primary" {{ $isProcessing ? 'disabled' : '' }}>Procesar Frente</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border border-dashed p-3 mb-0 bg-light-subtle">
                                            <h6 class="fs-12 text-uppercase fw-semibold mb-2 text-primary"><i class="ri-upload-cloud-2-line me-1"></i> Reemplazar Imagen Reverso</h6>
                                            <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'ine_back']) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="input-group input-group-sm">
                                                    <input type="file" name="ine_back" class="form-control" accept="image/*" required>
                                                    <button type="submit" class="btn btn-primary" {{ $isProcessing ? 'disabled' : '' }}>Procesar Reverso</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    @endif





                    <!-- 9. HISTORIAL DE LITIGIOS -->
                    <div class="accordion-item shadow-sm border mb-3">
                        <h2 class="accordion-header" id="headingLitigios">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLitigios" aria-expanded="false" aria-controls="collapseLitigios">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div class="fw-semibold text-dark fs-14">
                                        <i class="ri-scales-3-fill text-primary me-2 align-middle fs-18"></i> Antecedentes Judiciales y Litigios
                                    </div>
                                    <div>
                                        @if(!$litigiosQuery)
                                            <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                        @elseif($litigiosQuery->status === 'pending' || $litigiosQuery->status === 'processing')
                                            <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando</span>
                                        @elseif($litigiosQuery->status === 'failed')
                                            <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                        @elseif($litigiosQuery->status === 'completed')
                                            @if($hasLitigiosAlert)
                                                <span class="badge bg-warning text-dark py-1 px-2"><i class="ri-folders-fill me-1"></i> LITIGIOS ENCONTRADOS</span>
                                            @else
                                                <span class="badge bg-success text-white py-1 px-2"><i class="ri-checkbox-circle-fill me-1"></i> Sin Expedientes Judiciales</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapseLitigios" class="accordion-collapse collapse" aria-labelledby="headingLitigios" data-bs-parent="#sourcesAccordion">
                            <div class="accordion-body">
                                @if(!$litigiosQuery)
                                    <div class="text-center py-3">
                                        <p class="text-muted mb-2">La búsqueda de demandas y expedientes judiciales a nombre del sujeto en juzgados de México aún no se ha iniciado.</p>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'litigios']) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                                <i class="ri-play-circle-line me-1"></i> Consultar Fuente
                                            </button>
                                        </form>
                                    </div>
                                @elseif($litigiosQuery->status === 'pending' || $litigiosQuery->status === 'processing')
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                        <span>Buscando registros de demandas y juicios legales. Espere...</span>
                                    </div>
                                @elseif($litigiosQuery->status === 'failed')
                                    <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center mb-0">
                                        <span><strong>Error:</strong> {{ $litigiosQuery->error_message }}</span>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'litigios']) }}" method="POST" class="ms-3">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>Re-Consultar</button>
                                        </form>
                                    </div>
                                @elseif($litigiosQuery->status === 'completed')
                                    @php $litigData = $litigiosQuery->result?->processed_data ?? []; @endphp
                                    @if(empty($litigData['juicios'] ?? []))
                                        <div class="alert alert-success border-0 mb-0">
                                            <h6 class="alert-heading text-success fw-semibold"><i class="ri-checkbox-circle-line me-1"></i> Historial Limpio</h6>
                                            <p class="mb-0">No se encontraron expedientes judiciales, demandas o litigios civiles, mercantiles, laborales o penales vinculados a este nombre en juzgados estatales o federales.</p>
                                        </div>
                                    @else
                                        <div class="alert alert-warning border-0 mb-3">
                                            <h6 class="alert-heading text-warning fw-semibold"><i class="ri-alert-fill me-1"></i> Litigios Registrados</h6>
                                            <p class="mb-0">Se han localizado los siguientes expedientes y procesos judiciales vinculados al sujeto:</p>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-striped align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Expediente</th>
                                                        <th>Juzgado / Tribunal</th>
                                                        <th>Fuero</th>
                                                        <th>Materia / Acción</th>
                                                        <th>Actor / Demandante</th>
                                                        <th>Demandado</th>
                                                        <th>Fecha de Publicación</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($litigData['juicios'] as $juicio)
                                                    <tr>
                                                        <td><code>{{ $juicio['expediente'] ?? 'N/A' }}</code></td>
                                                        <td class="fw-semibold text-dark">{{ $juicio['juzgado'] ?? 'N/A' }}</td>
                                                        <td><span class="badge bg-light text-dark fs-12">{{ $juicio['fuero'] ?? 'Local' }}</span></td>
                                                        <td><span class="badge bg-warning-subtle text-warning fs-12">{{ $juicio['materia'] ?? 'Civil' }}</span></td>
                                                        <td>{{ $juicio['actor'] ?? 'N/A' }}</td>
                                                        <td>{{ $juicio['demandado'] ?? 'N/A' }}</td>
                                                        <td>{{ isset($juicio['fecha']) ? \Carbon\Carbon::parse($juicio['fecha'])->format('d/m/Y') : 'N/A' }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- 3. HISTORIAL LABORAL IMSS / SEMANAS COTIZADAS (NSS) -->
                    @if($subject->tipo === 'persona_fisica')
                    <div class="accordion-item shadow-sm border mb-3">
                        <h2 class="accordion-header" id="headingNss">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNss" aria-expanded="false" aria-controls="collapseNss">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div class="fw-semibold text-dark fs-14">
                                        <i class="ri-hospital-fill text-primary me-2 align-middle fs-18"></i> Historial Laboral IMSS / Semanas Cotizadas (NSS)
                                    </div>
                                    <div>
                                        @if(!$nssQuery)
                                            <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                        @elseif($nssQuery->status === 'pending' || $nssQuery->status === 'processing')
                                            <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando</span>
                                        @elseif($nssQuery->status === 'failed')
                                            <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                        @elseif($nssQuery->status === 'completed')
                                            @php $nData = $nssQuery->result?->processed_data ?? []; @endphp
                                            <span class="badge {{ ($nData['activo_actualmente'] ?? false) ? 'bg-success' : 'bg-secondary' }} text-white py-1 px-2">
                                                <i class="ri-checkbox-circle-fill me-1"></i> {{ ($nData['activo_actualmente'] ?? false) ? 'Activo IMSS' : 'Baja IMSS' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapseNss" class="accordion-collapse collapse" aria-labelledby="headingNss" data-bs-parent="#sourcesAccordion">
                            <div class="accordion-body">
                                @if(!$nssQuery)
                                    <div class="text-center py-3">
                                        <p class="text-muted mb-2">La consulta del historial de empleadores y semanas cotizadas ante el IMSS aún no se ha iniciado.</p>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'nss_imss']) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                                <i class="ri-play-circle-line me-1"></i> Consultar Fuente
                                            </button>
                                        </form>
                                    </div>
                                @elseif($nssQuery->status === 'pending' || $nssQuery->status === 'processing')
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                        <span>Solicitando UUID y verificando historial afiliatorio ante el IMSS...</span>
                                    </div>
                                @elseif($nssQuery->status === 'failed')
                                    <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center mb-0">
                                        <span><strong>Error:</strong> {{ $nssQuery->error_message }}</span>
                                        <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'nss_imss']) }}" method="POST" class="ms-3">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>Re-Consultar</button>
                                        </form>
                                    </div>
                                @elseif($nssQuery->status === 'completed')
                                    @php
                                        $nData = $nssQuery->result?->processed_data ?? [];
                                        $empleosList = $nData['empleos'] ?? $nData['historial_empleos'] ?? [];
                                        $ultimoPatron = !empty($empleosList) ? ($empleosList[0]['patron'] ?? '—') : ($nData['ultimo_patron'] ?? '—');
                                    @endphp
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-3 col-6">
                                            <div class="border rounded p-2 text-center bg-light-subtle">
                                                <span class="fs-11 text-muted d-block text-uppercase">NSS</span>
                                                <code class="fs-13 fw-bold text-dark">{{ $nData['nss'] ?? 'N/A' }}</code>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="border rounded p-2 text-center bg-light-subtle">
                                                <span class="fs-11 text-muted d-block text-uppercase">Semanas Cotizadas</span>
                                                <span class="fs-14 fw-bold text-primary">{{ $nData['semanas_cotizadas'] ?? 0 }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="border rounded p-2 text-center bg-light-subtle">
                                                <span class="fs-11 text-muted d-block text-uppercase">Estatus Afiliatorio</span>
                                                <span class="badge {{ ($nData['activo_actualmente'] ?? false) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} fs-11 mt-1">
                                                    {{ ($nData['activo_actualmente'] ?? false) ? 'VIGENTE / ACTIVO' : 'BAJA' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="border rounded p-2 text-center bg-light-subtle">
                                                <span class="fs-11 text-muted d-block text-uppercase">Último Patrón</span>
                                                <span class="fs-12 fw-semibold text-dark text-truncate d-block">{{ $ultimoPatron }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    @if(!empty($empleosList))
                                        <div class="table-responsive">
                                            <table class="table table-striped align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Empresa / Patrón</th>
                                                        <th>Registro Patronal</th>
                                                        <th>Entidad Federativa</th>
                                                        <th>Fecha Alta</th>
                                                        <th>Fecha Baja / Estatus</th>
                                                        <th>Salario Base</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($empleosList as $emp)
                                                    <tr>
                                                        <td class="fw-bold text-dark">{{ $emp['patron'] ?? 'N/A' }}</td>
                                                        <td><code>{{ $emp['registro_patronal'] ?? 'N/A' }}</code></td>
                                                        <td>{{ $emp['entidad_federativa'] ?? 'N/A' }}</td>
                                                        <td>{{ $emp['fecha_alta'] ?? 'N/A' }}</td>
                                                        <td>
                                                            @if(strtolower($emp['fecha_baja'] ?? '') === 'vigente')
                                                                <span class="badge bg-success-subtle text-success">Vigente</span>
                                                            @else
                                                                <span class="text-muted">{{ $emp['fecha_baja'] ?? 'N/A' }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="fw-semibold text-primary">{{ $emp['salario_base'] ?? 'N/A' }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted fs-12 mb-0">Sin desglose de empleos registrados.</p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif


                    </div> {{-- /tierSection2 --}}

                    {{-- SECCIÓN 3: NIVEL 3 — VERIFICACIÓN EJECUTIVA --}}
                    <div class="tier-section" id="tierSection3">
                        <div class="alert alert-warning border-0 d-flex align-items-center mb-3 shadow-sm py-2 px-3 mt-4">
                            <i class="ri-award-fill fs-18 me-2 text-warning-emphasis"></i>
                            <span class="fw-bold fs-13 flex-grow-1 text-uppercase text-warning-emphasis">NIVEL 3 — Verificación Ejecutiva (Certificados CSD, OSINT y Perfil Digital)</span>
                        </div>

                        <!-- 9. CERTIFICADOS CSD -->
                        <div class="accordion-item shadow-sm border mb-3">
                            <h2 class="accordion-header" id="headingCsd">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCsd" aria-expanded="false" aria-controls="collapseCsd">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div class="fw-semibold text-dark fs-14">
                                            <i class="ri-key-2-fill text-primary me-2 align-middle fs-18"></i> Certificados CSD y e-Firma (SAT)
                                        </div>
                                        <div>
                                            @if(!$csdQuery)
                                                <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                            @elseif($csdQuery->status === 'pending' || $csdQuery->status === 'processing')
                                                <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando</span>
                                            @elseif($csdQuery->status === 'failed')
                                                <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                            @elseif($csdQuery->status === 'completed')
                                                <span class="badge bg-success text-white py-1 px-2">Completed</span>
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapseCsd" class="accordion-collapse collapse" aria-labelledby="headingCsd" data-bs-parent="#sourcesAccordion">
                                <div class="accordion-body">
                                    @if(!$csdQuery)
                                        <div class="text-center py-3">
                                            <p class="text-muted mb-2">La recuperación de certificados de sellos de este sujeto aún no se ha iniciado.</p>
                                            <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'csd']) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                                    <i class="ri-play-circle-line me-1"></i> Consultar Fuente
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($csdQuery->status === 'pending' || $csdQuery->status === 'processing')
                                        <div class="text-center py-3">
                                            <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                            <span>Recuperando historial de sellos SAT. Espere...</span>
                                        </div>
                                    @elseif($csdQuery->status === 'failed')
                                        <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center mb-0">
                                            <span><strong>Error:</strong> {{ $csdQuery->error_message }}</span>
                                            <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'csd']) }}" method="POST" class="ms-3">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>Re-Consultar</button>
                                            </form>
                                        </div>
                                    @elseif($csdQuery->status === 'completed')
                                        @php $certs = $csdQuery->result?->processed_data['certificados'] ?? []; @endphp
                                        @if(empty($certs))
                                            <div class="text-center text-muted py-3">No se detectaron certificados asociados a este RFC en las bases públicas.</div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-striped align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Número de Serie</th>
                                                            <th>Tipo</th>
                                                            <th>Estatus</th>
                                                            <th>Inicio de Vigencia</th>
                                                            <th>Fin de Vigencia</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($certs as $cert)
                                                        <tr>
                                                            <td><code>{{ $cert['numero_serie'] ?? '' }}</code></td>
                                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $cert['tipo'] ?? 'CSD' }}</span></td>
                                                            <td>
                                                                @php $st = strtolower($cert['estado'] ?? ''); @endphp
                                                                <span class="badge bg-{{ $st === 'activo' ? 'success' : ($st === 'revocado' ? 'danger' : 'warning') }}-subtle text-{{ $st === 'activo' ? 'success' : ($st === 'revocado' ? 'danger' : 'warning') }}">
                                                                    {{ ucfirst($cert['estado'] ?? 'Caduco') }}
                                                                </span>
                                                            </td>
                                                            <td>{{ isset($cert['fecha_inicio']) ? \Carbon\Carbon::parse($cert['fecha_inicio'])->format('d/m/Y H:i') : 'N/A' }}</td>
                                                            <td>{{ isset($cert['fecha_fin']) ? \Carbon\Carbon::parse($cert['fecha_fin'])->format('d/m/Y H:i') : 'N/A' }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    @endif
                            </div>
                        </div>

                        <!-- 10. PRESENCIA DIGITAL Y HUELLA EN REDES (OSINT) -->
                        <div class="accordion-item shadow-sm border mb-3">
                            <h2 class="accordion-header" id="headingOsint">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOsint" aria-expanded="false" aria-controls="collapseOsint">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div class="fw-semibold text-dark fs-14">
                                            <i class="ri-global-line text-primary me-2 align-middle fs-18"></i> Presencia Digital y Huella en Redes (OSINT)
                                        </div>
                                        <div>
                                            @if(!$osintQuery)
                                                <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                            @elseif($osintQuery->status === 'pending' || $osintQuery->status === 'processing')
                                                <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando</span>
                                            @elseif($osintQuery->status === 'failed')
                                                <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                            @elseif($osintQuery->status === 'completed')
                                                @php $oData = $osintQuery->result?->processed_data ?? []; @endphp
                                                <span class="badge bg-success text-white py-1 px-2">
                                                    <i class="ri-checkbox-circle-fill me-1"></i> {{ count($oData['plataformas_encontradas'] ?? $oData['profiles'] ?? []) }} Perfiles Encontrados
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapseOsint" class="accordion-collapse collapse" aria-labelledby="headingOsint" data-bs-parent="#sourcesAccordion">
                                <div class="accordion-body">
                                    @if(!$osintQuery)
                                        <div class="text-center py-3">
                                            <p class="text-muted mb-2">El rastreo de huella digital y presencia en redes públicas (OSINT) aún no se ha iniciado.</p>
                                            <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'presencia_en_linea']) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                                    <i class="ri-play-circle-line me-1"></i> Consultar Fuente
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($osintQuery->status === 'pending' || $osintQuery->status === 'processing')
                                        <div class="text-center py-3">
                                            <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                            <span>Realizando búsqueda OSINT en redes y plataformas públicas...</span>
                                        </div>
                                    @elseif($osintQuery->status === 'failed')
                                        <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center mb-0">
                                            <span><strong>Error:</strong> {{ $osintQuery->error_message }}</span>
                                            <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'presencia_en_linea']) }}" method="POST" class="ms-3">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>Re-Consultar</button>
                                            </form>
                                        </div>
                                    @elseif($osintQuery->status === 'completed')
                                        @php
                                            $oData = $osintQuery->result?->processed_data ?? [];
                                            $perfiles = $oData['plataformas_encontradas'] ?? $oData['profiles'] ?? [];
                                        @endphp
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="border rounded p-3 bg-light-subtle h-100">
                                                    <h6 class="fs-12 text-muted text-uppercase mb-2">Resumen de Coincidencias OSINT</h6>
                                                    <div class="mb-2"><strong>Perfiles Localizados:</strong> <span class="badge bg-primary fs-12">{{ count($perfiles) }}</span></div>
                                                    <div class="mb-1 text-muted fs-12"><strong>Nivel de Confianza:</strong> Alta</div>
                                                    <div class="text-muted fs-12"><strong>Base Legal:</strong> Interés Legítimo / Fuentes Públicas</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="border rounded p-3 bg-light-subtle h-100">
                                                    <h6 class="fs-12 text-muted text-uppercase mb-2">Plataformas y Redes Detectadas</h6>
                                                    @if(!empty($perfiles))
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach($perfiles as $perf)
                                                                <span class="badge bg-info-subtle text-info fs-11 p-2">
                                                                    <i class="ri-link me-1"></i> {{ is_array($perf) ? ($perf['platform'] ?? $perf['site'] ?? 'Plataforma') : $perf }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-muted fs-12 mb-0">Sin perfiles públicos correlacionados.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 4. ANÁLISIS DE PERFIL DIGITAL Y ENRIQUECIMIENTO POR NOMBRE -->
                        <div class="accordion-item shadow-sm border mb-3">
                            <h2 class="accordion-header" id="headingIdentidadDigital">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIdentidadDigital" aria-expanded="false" aria-controls="collapseIdentidadDigital">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div class="fw-semibold text-dark fs-14">
                                            <i class="ri-user-search-line text-primary me-2 align-middle fs-18"></i> Análisis de Perfil Digital y Enriquecimiento por Nombre
                                        </div>
                                        <div>
                                            @if(!$identidadDigitalQuery)
                                                <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                            @elseif($identidadDigitalQuery->status === 'pending' || $identidadDigitalQuery->status === 'processing')
                                                <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando</span>
                                            @elseif($identidadDigitalQuery->status === 'failed')
                                                <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                            @elseif($identidadDigitalQuery->status === 'completed')
                                                @php $idData = $identidadDigitalQuery->result?->processed_data ?? []; @endphp
                                                @if($idData['top_match'] ?? true)
                                                    <span class="badge bg-success text-white py-1 px-2"><i class="ri-checkbox-circle-fill me-1"></i> Perfil Único Confirmado</span>
                                                @else
                                                    <span class="badge bg-info text-white py-1 px-2"><i class="ri-information-fill me-1"></i> Coincidencia Colectiva</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapseIdentidadDigital" class="accordion-collapse collapse" aria-labelledby="headingIdentidadDigital" data-bs-parent="#sourcesAccordion">
                                <div class="accordion-body">
                                    @if(!$identidadDigitalQuery)
                                        <div class="text-center py-3">
                                            <p class="text-muted mb-2">La búsqueda de perfil digital y enriquecimiento por nombre aún no se ha iniciado.</p>
                                            <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'identidad_digital']) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                                    <i class="ri-play-circle-line me-1"></i> Consultar Fuente
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($identidadDigitalQuery->status === 'pending' || $identidadDigitalQuery->status === 'processing')
                                        <div class="text-center py-3">
                                            <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                            <span>Analizando huella digital y perfiles públicos en NuFi...</span>
                                        </div>
                                    @elseif($identidadDigitalQuery->status === 'failed')
                                        <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center mb-0">
                                            <span><strong>Error:</strong> {{ $identidadDigitalQuery->error_message }}</span>
                                            <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'identidad_digital']) }}" method="POST" class="ms-3">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>Re-Consultar</button>
                                            </form>
                                        </div>
                                    @elseif($identidadDigitalQuery->status === 'completed' && $identidadDigitalQuery->result)
                                        @php $idData = $identidadDigitalQuery->result->processed_data ?? []; @endphp
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="border rounded p-3 bg-light-subtle h-100">
                                                    <h6 class="fs-12 text-muted text-uppercase mb-2">Presencia y Diagnóstico Anti-Homónimos</h6>
                                                    <div class="mb-2">
                                                        <strong>Dictamen de Coincidencia:</strong>
                                                        @if($idData['top_match'] ?? true)
                                                            <span class="badge bg-success-subtle text-success ms-1">ALTA CERTEZA (TOP MATCH)</span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning ms-1">MÚLTIPLES PERSONAS RELEVADAS</span>
                                                        @endif
                                                    </div>
                                                    <div class="mb-1 fs-12 text-muted"><strong>ID de Consulta NuFi:</strong> <code>{{ $idData['search_id'] ?? 'N/A' }}</code></div>
                                                    <div class="mb-1 fs-12 text-muted"><strong>Confiabilidad Calculada:</strong> <span class="badge bg-info-subtle text-info">{{ $idData['score_confiabilidad'] ?? 95 }}%</span></div>
                                                    
                                                    <h6 class="fs-12 text-muted text-uppercase mt-3 mb-2">Historial Laboral / Puestos Rastreados</h6>
                                                    @if(!empty($idData['jobs']))
                                                        <ul class="list-unstyled mb-0 fs-12">
                                                            @foreach($idData['jobs'] as $j)
                                                                <li class="mb-1 text-dark"><i class="ri-briefcase-line text-primary me-1"></i> {{ is_array($j) ? (($j['title'] ?? '') . ' - ' . ($j['organization'] ?? '')) : $j }}</li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <p class="text-muted fs-12 mb-0">Sin historial de empleos en redes públicas.</p>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="border rounded p-3 bg-light-subtle h-100">
                                                    <h6 class="fs-12 text-muted text-uppercase mb-2">Formación Académica</h6>
                                                    @if(!empty($idData['educations']))
                                                        <ul class="list-unstyled mb-2 fs-12">
                                                            @foreach($idData['educations'] as $edu)
                                                                <li class="mb-1 text-dark"><i class="ri-book-read-line text-info me-1"></i> {{ is_array($edu) ? (($edu['degree'] ?? '') . ' ' . ($edu['school'] ?? '')) : $edu }}</li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <p class="text-muted fs-12 mb-2">Sin registros académicos detectados.</p>
                                                    @endif

                                                    <h6 class="fs-12 text-muted text-uppercase mt-3 mb-2">Perfiles y Enlaces Encontrados</h6>
                                                    @if(!empty($idData['urls']))
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach($idData['urls'] as $u)
                                                                @php $linkUrl = is_array($u) ? ($u['url'] ?? '#') : $u; @endphp
                                                                <a href="{{ $linkUrl }}" target="_blank" class="btn btn-xs btn-outline-primary fs-11">
                                                                    <i class="ri-external-link-line me-1"></i> {{ parse_url($linkUrl, PHP_URL_HOST) ?? 'Enlace' }}
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-muted fs-12 mb-0">Sin perfiles web correlacionados.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div> {{-- /tierSection3 --}}

                    {{-- SECCIÓN 4: NIVEL 4 — VERIFICACIÓN CORPORATIVA --}}
                    @if($subject->tipo === 'persona_moral')
                    <div class="tier-section" id="tierSection4">
                        <div class="alert alert-dark border-0 d-flex align-items-center mb-3 shadow-sm py-2 px-3 mt-4">
                            <i class="ri-building-4-fill fs-18 me-2"></i>
                            <span class="fw-bold fs-13 flex-grow-1 text-uppercase">NIVEL 4 — Verificación Corporativa (Registro Público SIGER)</span>
                        </div>

                        <!-- 10. REGISTRO PÚBLICO SIGER -->
                        <div class="accordion-item shadow-sm border mb-3">
                            <h2 class="accordion-header" id="headingSiger">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSiger" aria-expanded="false" aria-controls="collapseSiger">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div class="fw-semibold text-dark fs-14">
                                            <i class="ri-bank-fill text-primary me-2 align-middle fs-18"></i> Registro Público de Comercio (SIGER)
                                        </div>
                                        <div>
                                            @if($subject->tipo === 'persona_fisica')
                                                <span class="badge bg-light text-muted py-1 px-2 text-decoration-line-through">No Aplica</span>
                                            @elseif(!$sigerQuery)
                                                <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                            @elseif($sigerQuery->status === 'pending' || $sigerQuery->status === 'processing')
                                                <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando</span>
                                            @elseif($sigerQuery->status === 'failed')
                                                <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                            @elseif($sigerQuery->status === 'completed')
                                                <span class="badge bg-success text-white py-1 px-2">Completed</span>
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapseSiger" class="accordion-collapse collapse" aria-labelledby="headingSiger" data-bs-parent="#sourcesAccordion">
                                <div class="accordion-body">
                                    @if($subject->tipo === 'persona_fisica')
                                        <div class="alert alert-warning border-0 mb-0">
                                            <h6 class="alert-heading text-warning fw-semibold mb-1"><i class="ri-alert-line align-middle me-1"></i> Fuente Excluida</h6>
                                            <p class="mb-0">La consulta del Registro Público de Comercio (SIGER) aplica únicamente para Sociedades Mercantiles (Personas Morales). Ha sido excluida por el tipo de sujeto.</p>
                                        </div>
                                    @elseif(!$sigerQuery)
                                        <div class="text-center py-3">
                                            <p class="text-muted mb-2">La consulta mercantiles y composición de socios en SIGER aún no se ha iniciado.</p>
                                            <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'siger']) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                                    <i class="ri-play-circle-line me-1"></i> Consultar Fuente
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($sigerQuery->status === 'pending' || $sigerQuery->status === 'processing')
                                        <div class="text-center py-3">
                                            <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                            <span>Buscando actas constitutivas mercantiles. Espere...</span>
                                        </div>
                                    @elseif($sigerQuery->status === 'failed')
                                        <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center mb-0">
                                            <span><strong>Error:</strong> {{ $sigerQuery->error_message }}</span>
                                            <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'siger']) }}" method="POST" class="ms-3">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>Re-Consultar</button>
                                            </form>
                                        </div>
                                    @elseif($sigerQuery->status === 'completed')
                                        @php $results = $sigerQuery->result?->processed_data['resultados'] ?? []; @endphp
                                        @if(empty($results))
                                            <div class="text-center text-muted py-3">No se localizaron registros comerciales en SIGER vinculados a esta Razón Social.</div>
                                        @else
                                            @foreach($results as $res)
                                            <div class="card border border-dashed mb-3">
                                                <div class="card-header bg-light-subtle align-items-center d-flex pb-2">
                                                    <h6 class="card-title mb-0 flex-grow-1 text-primary">Folio Mercantil Electrónico (FME): #{{ $res['fme'] ?? '' }}</h6>
                                                    <span class="badge bg-success-subtle text-success">{{ $res['entidad_federativa'] ?? 'CDMX' }}</span>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row mb-2">
                                                        <div class="col-sm-6">
                                                            <span class="text-muted">Fecha Constitución:</span>
                                                            <span class="fw-semibold d-block text-dark">{{ isset($res['fecha_constitucion']) ? \Carbon\Carbon::parse($res['fecha_constitucion'])->format('d/m/Y') : 'N/A' }}</span>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <span class="text-muted">Capital Social de Inicio:</span>
                                                            <span class="fw-semibold d-block text-dark">${{ number_format($res['capital_social'] ?? 0, 2) }} MXN</span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <span class="text-muted">Objeto Social Constituido:</span>
                                                        <p class="text-muted fs-12 mb-0" style="text-align: justify;">{{ $res['objeto_social'] ?? 'N/A' }}</p>
                                                    </div>
                                                    <h6 class="text-uppercase text-muted fs-11 mb-2">Socios y Accionistas Registrados</h6>
                                                    <div class="table-responsive table-card">
                                                        <table class="table table-sm table-nowrap mb-0">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Nombre Completo</th>
                                                                    <th>Participación</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($res['socios'] ?? [] as $socio)
                                                                <tr>
                                                                    <td class="fw-semibold text-dark">{{ $socio['nombre'] ?? '' }}</td>
                                                                    <td><span class="badge bg-primary-subtle text-primary">{{ $socio['participacion'] ?? '' }}</span></td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 11. REGISTRO DE MARCAS IMPI -->
                        <div class="accordion-item shadow-sm border mb-3">
                            <h2 class="accordion-header" id="headingMarcas">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMarcas" aria-expanded="false" aria-controls="collapseMarcas">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div class="fw-semibold text-dark fs-14">
                                            <i class="ri-trademark-fill text-primary me-2 align-middle fs-18"></i> Propiedad Industrial e Intelectual (IMPI)
                                        </div>
                                        <div>
                                            @if(!$marcasQuery)
                                                <span class="badge bg-secondary-subtle text-secondary py-1 px-2">No Iniciado</span>
                                            @elseif($marcasQuery->status === 'pending' || $marcasQuery->status === 'processing')
                                                <span class="badge bg-warning-subtle text-warning py-1 px-2">Procesando</span>
                                            @elseif($marcasQuery->status === 'failed')
                                                <span class="badge bg-danger-subtle text-danger py-1 px-2">Error de Consulta</span>
                                            @elseif($marcasQuery->status === 'completed')
                                                <span class="badge bg-success text-white py-1 px-2">Completed</span>
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapseMarcas" class="accordion-collapse collapse" aria-labelledby="headingMarcas" data-bs-parent="#sourcesAccordion">
                                <div class="accordion-body">
                                    @if(!$marcasQuery)
                                        <div class="text-center py-3">
                                            <p class="text-muted mb-2">La búsqueda de marcas y patentes a nombre de la empresa en las bases del IMPI aún no se ha iniciado.</p>
                                            <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'marcas']) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                                    <i class="ri-play-circle-line me-1"></i> Consultar Fuente
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($marcasQuery->status === 'pending' || $marcasQuery->status === 'processing')
                                        <div class="text-center py-3">
                                            <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                            <span>Buscando marcas registradas. Espere...</span>
                                        </div>
                                    @elseif($marcasQuery->status === 'failed')
                                        <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center mb-0">
                                            <span><strong>Error:</strong> {{ $marcasQuery->error_message }}</span>
                                            <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'marcas']) }}" method="POST" class="ms-3">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>Re-Consultar</button>
                                            </form>
                                        </div>
                                    @elseif($marcasQuery->status === 'completed')
                                        @php $marcas = $marcasQuery->result?->processed_data['marcas'] ?? []; @endphp
                                        @if(empty($marcas))
                                            <div class="text-center text-muted py-3">No se detectaron marcas o solicitudes registradas a nombre del titular en el IMPI.</div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-striped align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Registro</th>
                                                            <th>Expediente</th>
                                                            <th>Denominación</th>
                                                            <th>Titular</th>
                                                            <th>Clase Nice</th>
                                                            <th>Concesión</th>
                                                            <th>Estatus</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($marcas as $marca)
                                                        <tr>
                                                            <td><code>{{ $marca['numero_registro'] ?? 'N/A' }}</code></td>
                                                            <td><code>{{ $marca['numero_expediente'] ?? 'N/A' }}</code></td>
                                                            <td class="fw-semibold text-dark">{{ $marca['denominacion'] ?? '' }}</td>
                                                            <td>{{ $marca['titular'] ?? '' }}</td>
                                                            <td><span class="badge bg-light text-dark">Clase {{ $marca['clase_nice'] ?? '' }}</span></td>
                                                            <td>{{ isset($marca['fecha_concesion']) ? \Carbon\Carbon::parse($marca['fecha_concesion'])->format('d/m/Y') : 'N/A' }}</td>
                                                            <td><span class="badge bg-success-subtle text-success">{{ $marca['estatus'] ?? 'REGISTRADA' }}</span></td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif {{-- /tierSection4 --}}

                </div>
            </div>
        </div>
    </div>
</div>
{{-- ═══════════════════════════════════════════════════════════════════════
     DOCUMENTOS DE IDENTIDAD — Visor seguro INE / Selfie / Comprobante
     ═══════════════════════════════════════════════════════════════════════ --}}
@php
    $docSlots = [
        ['type' => 'ine_front',        'label' => 'INE Frente',            'icon' => 'ri-id-card-line',      'path' => $subject->ine_front_path,        'ratio' => '1.586'],
        ['type' => 'ine_back',         'label' => 'INE Reverso',           'icon' => 'ri-id-card-line',      'path' => $subject->ine_back_path,         'ratio' => '1.586'],
        ['type' => 'selfie',           'label' => 'Selfie',                'icon' => 'ri-user-smile-line',   'path' => $subject->selfie_path,           'ratio' => '1'],
        ['type' => 'proof_of_address', 'label' => 'Comprobante Domicilio', 'icon' => 'ri-home-4-line',       'path' => $subject->proof_of_address_path, 'ratio' => '1.586'],
        ['type' => 'consent',          'label' => 'Consentimiento',        'icon' => 'ri-shield-check-line', 'path' => $subject->consent_document_path, 'ratio' => '1.586'],
    ];
@endphp
<div class="card mt-3">
    <div class="card-header d-flex align-items-center border-0 pb-0">
        <i class="ri-folder-image-line me-2 text-primary fs-18"></i>
        <h6 class="card-title mb-0 fw-bold flex-grow-1">Documentos de Identidad</h6>
        @if($subject->enrollment_completed_at)
            <span class="badge bg-success-subtle text-success fs-11">
                <i class="ri-checkbox-circle-line me-1"></i>
                Enrolamiento {{ \Carbon\Carbon::parse($subject->enrollment_completed_at)->diffForHumans() }}
            </span>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($docSlots as $slot)
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">

                {{-- Preview --}}
                <div class="border rounded-3 overflow-hidden bg-body-secondary position-relative"
                     style="aspect-ratio:{{ $slot['ratio'] }};">
                    @if($slot['path'])
                        @php $ext = strtolower(pathinfo($slot['path'], PATHINFO_EXTENSION)); @endphp
                        @if(in_array($ext, ['jpg','jpeg','png','webp']))
                            <img src="{{ route('tenant.subjects.document', [$subject->id, $slot['type']]) }}"
                                 alt="{{ $slot['label'] }}" class="w-100 h-100"
                                 style="object-fit:cover; cursor:pointer;"
                                 onclick="openDocModal('{{ route('tenant.subjects.document', [$subject->id, $slot['type']]) }}','{{ $slot['label'] }}')">
                        @else
                            <a href="{{ route('tenant.subjects.document', [$subject->id, $slot['type']]) }}"
                               target="_blank"
                               class="d-flex flex-column align-items-center justify-content-center h-100 text-decoration-none">
                                <i class="ri-file-pdf-line fs-30 text-danger"></i>
                                <span class="fs-10 text-muted mt-1">Ver PDF</span>
                            </a>
                        @endif
                    @else
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                            <i class="{{ $slot['icon'] }} fs-28 mb-1"></i>
                            <span class="fs-10">Sin documento</span>
                        </div>
                    @endif
                </div>

                {{-- Label --}}
                <p class="text-center text-muted fs-11 mt-1 mb-1">
                    <i class="{{ $slot['icon'] }} me-1"></i>{{ $slot['label'] }}
                </p>

                {{-- Acciones: Subir / Reemplazar + Borrar --}}
                <div class="d-flex gap-1">
                    <form action="{{ route('tenant.subjects.document.upload', [$subject->id, $slot['type']]) }}"
                          method="POST" enctype="multipart/form-data"
                          class="flex-grow-1" id="upForm_{{ $slot['type'] }}">
                        @csrf
                        <input type="file" name="document"
                               id="upFile_{{ $slot['type'] }}"
                               accept="image/*,application/pdf"
                               class="d-none"
                               onchange="document.getElementById('upForm_{{ $slot['type'] }}').submit()">
                        <button type="button"
                                onclick="document.getElementById('upFile_{{ $slot['type'] }}').click()"
                                class="btn btn-sm btn-outline-primary w-100 py-1 fs-11"
                                title="{{ $slot['path'] ? 'Reemplazar archivo' : 'Subir archivo' }}">
                            <i class="ri-upload-2-line me-1"></i>{{ $slot['path'] ? 'Reemplazar' : 'Subir' }}
                        </button>
                    </form>
                    @if($slot['path'])
                    <form action="{{ route('tenant.subjects.document.delete', [$subject->id, $slot['type']]) }}"
                          method="POST"
                          onsubmit="return confirm('¿Borrar {{ $slot['label'] }}? Esta acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 fs-11" title="Borrar">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </form>
                    @endif
                </div>

            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Modal visor de imagen --}}
<div class="modal fade" id="docImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 py-2 px-3">
                <h6 class="modal-title text-white fs-13" id="docImageModalLabel"></h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2 text-center">
                <img id="docImageModalImg" src="" alt="" class="img-fluid rounded" style="max-height:82vh; object-fit:contain;">
            </div>
        </div>
    </div>
</div>
<script>
function openDocModal(src, title) {
    const img = document.getElementById('docImageModalImg');
    img.src = ''; img.src = src;
    document.getElementById('docImageModalLabel').textContent = title;
    new bootstrap.Modal(document.getElementById('docImageModal')).show();
}
</script>


{{-- ═══════════════════════════════════════════════════════════════════════
     TARJETAS TIER 2 — CURP / DOMICILIO / NSS / SCORE CREDITICIO
     Se muestran en una fila de 2 columnas por par para mayor densidad.
     ═══════════════════════════════════════════════════════════════════════ --}}
@php
    $curpQuery       = $queries->firstWhere('source_type', 'curp');
    $curpData        = $curpQuery?->result?->processed_data ?? [];
    $domicilioQuery  = $queries->firstWhere('source_type', 'comprobante_domicilio');
    $domicilioData   = $domicilioQuery?->result?->processed_data ?? [];
    $nssQuery        = $queries->firstWhere('source_type', 'nss_imss');
    $nssData         = $nssQuery?->result?->processed_data ?? [];
    $scoreQuery      = $queries->firstWhere('source_type', 'score_crediticio');
    $scoreData       = $scoreQuery?->result?->processed_data ?? [];

    // Score color map
    $scoreColores = [
        'Excelente' => 'success',
        'Bueno'     => 'primary',
        'Regular'   => 'warning',
        'Malo'      => 'danger',
    ];
    $scoreColor = $scoreColores[$scoreData['rango_score'] ?? ''] ?? 'secondary';
@endphp

@if($curpQuery || $domicilioQuery || $nssQuery || $scoreQuery)
<div class="row mt-3 g-3">

    {{-- ── CURP / RENAPO ─────────────────────────────────────────────── --}}
    @if($curpQuery)
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center border-0 pb-0">
                <i class="ri-fingerprint-line me-2 text-primary fs-18"></i>
                <h6 class="card-title mb-0 fw-bold flex-grow-1">Validación CURP / RENAPO</h6>
                @if($curpQuery->status === 'completed')
                    <span class="badge {{ ($curpData['valida'] ?? false) ? 'bg-success' : 'bg-danger' }} fs-11">
                        {{ ($curpData['valida'] ?? false) ? '✓ Válida' : '✗ Inválida' }}
                    </span>
                @elseif(in_array($curpQuery->status, ['pending','processing']))
                    <span class="badge bg-info-subtle text-info fs-11"><span class="spinner-border spinner-border-sm" style="width:10px;height:10px;"></span></span>
                @else
                    <span class="badge bg-danger-subtle text-danger fs-11">Error</span>
                @endif
            </div>
            <div class="card-body pt-2 fs-13">
                @if($curpQuery->status === 'completed' && !empty($curpData))
                    <div class="row g-2">
                        <div class="col-6"><span class="text-muted d-block fs-11">CURP</span><code class="fs-12">{{ $curpData['curp'] ?? $subject->curp }}</code></div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Estatus RENAPO</span><strong>{{ $curpData['estatus_curp'] ?? '—' }}</strong></div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Nombre</span>{{ trim(($curpData['nombre'] ?? '') . ' ' . ($curpData['primer_apellido'] ?? '') . ' ' . ($curpData['segundo_apellido'] ?? '')) ?: '—' }}</div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Fecha Nac.</span>{{ $curpData['fecha_nacimiento'] ?? '—' }}</div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Sexo</span>{{ $curpData['sexo'] ?? '—' }}</div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Entidad Nac.</span>{{ $curpData['estado_nacimiento'] ?? '—' }}</div>
                    </div>
                @elseif($curpQuery->status === 'failed')
                    <p class="text-danger mb-0 fs-12"><i class="ri-error-warning-line me-1"></i>Error al consultar RENAPO.</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ── Comprobante de Domicilio ───────────────────────────────────── --}}
    @if($domicilioQuery)
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center border-0 pb-0">
                <i class="ri-home-4-line me-2 text-info fs-18"></i>
                <h6 class="card-title mb-0 fw-bold flex-grow-1">Comprobante de Domicilio (OCR)</h6>
                @if($domicilioQuery->status === 'completed')
                    <span class="badge {{ ($domicilioData['valido'] ?? false) ? 'bg-success' : 'bg-warning' }} fs-11">
                        {{ ($domicilioData['valido'] ?? false) ? '✓ Válido' : '⚠ Revisar' }}
                    </span>
                @elseif(in_array($domicilioQuery->status, ['pending','processing']))
                    <span class="badge bg-info-subtle text-info fs-11"><span class="spinner-border spinner-border-sm" style="width:10px;height:10px;"></span></span>
                @else
                    <span class="badge bg-danger-subtle text-danger fs-11">Error</span>
                @endif
            </div>
            <div class="card-body pt-2 fs-13">
                @if($domicilioQuery->status === 'completed' && !empty($domicilioData))
                    <div class="row g-2">
                        <div class="col-6"><span class="text-muted d-block fs-11">Tipo</span>{{ $domicilioData['tipo_comprobante'] ?? '—' }}</div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Titular</span>{{ $domicilioData['titular'] ?? '—' }}</div>
                        <div class="col-12"><span class="text-muted d-block fs-11">Domicilio extraído</span>
                            {{ collect([$domicilioData['calle'] ?? null, $domicilioData['num_exterior'] ?? null, $domicilioData['colonia'] ?? null, $domicilioData['municipio'] ?? null, $domicilioData['estado'] ?? null])->filter()->implode(', ') ?: '—' }}
                            @if(!empty($domicilioData['codigo_postal'])) · C.P. {{ $domicilioData['codigo_postal'] }} @endif
                        </div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Emisión</span>{{ $domicilioData['periodo_facturado'] ?? $domicilioData['fecha_emision'] ?? '—' }}</div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Coincide con sujeto</span>
                            @if(isset($domicilioData['coincide_con_sujeto']))
                                <span class="badge {{ $domicilioData['coincide_con_sujeto'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} fs-11">
                                    {{ $domicilioData['coincide_con_sujeto'] ? '✓ Sí' : '✗ No' }}
                                </span>
                            @else —
                            @endif
                        </div>
                    </div>
                @elseif($domicilioQuery->status === 'failed')
                    <p class="text-danger mb-0 fs-12"><i class="ri-error-warning-line me-1"></i>Error en OCR del comprobante.</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ── NSS / IMSS ────────────────────────────────────────────────── --}}
    @if($nssQuery)
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center border-0 pb-0">
                <i class="ri-hospital-line me-2 text-warning fs-18"></i>
                <h6 class="card-title mb-0 fw-bold flex-grow-1">Historial Laboral IMSS (NSS)</h6>
                @if($nssQuery->status === 'completed')
                    <span class="badge {{ ($nssData['activo_actualmente'] ?? false) ? 'bg-success' : 'bg-secondary' }} fs-11">
                        {{ ($nssData['activo_actualmente'] ?? false) ? 'Activo IMSS' : 'Baja IMSS' }}
                    </span>
                @elseif(in_array($nssQuery->status, ['pending','processing']))
                    <span class="badge bg-info-subtle text-info fs-11"><span class="spinner-border spinner-border-sm" style="width:10px;height:10px;"></span></span>
                @else
                    <span class="badge bg-danger-subtle text-danger fs-11">Error</span>
                @endif
            </div>
            <div class="card-body pt-2 fs-13">
                @if($nssQuery->status === 'completed' && !empty($nssData))
                    @php
                        $empleosList = $nssData['empleos'] ?? $nssData['historial_empleos'] ?? [];
                        $ultimoPatron = !empty($empleosList) ? ($empleosList[0]['patron'] ?? '—') : ($nssData['ultimo_patron'] ?? '—');
                        $totalPatrones = count($empleosList) ?: ($nssData['total_patrones'] ?? '—');
                    @endphp
                    <div class="row g-2 mb-2">
                        <div class="col-6"><span class="text-muted d-block fs-11">NSS</span><code class="fs-12">{{ $nssData['nss'] ?? '—' }}</code></div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Semanas cotizadas</span><strong class="text-primary">{{ $nssData['semanas_cotizadas'] ?? '—' }}</strong></div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Último patrón</span>{{ $ultimoPatron }}</div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Total empleadores</span>{{ $totalPatrones }}</div>
                        @if(isset($nssData['semanas_descontadas']))
                        <div class="col-6"><span class="text-muted d-block fs-11">Sem. descontadas</span>{{ $nssData['semanas_descontadas'] }}</div>
                        @endif
                        @if(isset($nssData['semanas_reintegradas']))
                        <div class="col-6"><span class="text-muted d-block fs-11">Sem. reintegradas</span>{{ $nssData['semanas_reintegradas'] }}</div>
                        @endif
                    </div>
                    @if(!empty($empleosList))
                    <div class="table-responsive mt-2">
                        <table class="table table-xs table-hover fs-12 mb-0">
                            <thead class="table-light"><tr><th>Patrón</th><th>Reg. Patronal / Entidad</th><th>Estatus / Baja</th><th>Salario Base</th></tr></thead>
                            <tbody>
                                @foreach(array_slice($empleosList, 0, 5) as $emp)
                                <tr>
                                    <td><strong>{{ $emp['patron'] ?? '—' }}</strong></td>
                                    <td>{{ $emp['registro_patronal'] ?? '—' }}<br><small class="text-muted">{{ $emp['entidad_federativa'] ?? '' }}</small></td>
                                    <td>
                                        @if(strtolower($emp['fecha_baja'] ?? '') === 'vigente')
                                            <span class="badge bg-success-subtle text-success">Vigente</span>
                                        @else
                                            {{ $emp['fecha_baja'] ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="fw-semibold text-primary">{{ $emp['salario_base'] ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                @elseif($nssQuery->status === 'failed')
                    <p class="text-danger mb-0 fs-12"><i class="ri-error-warning-line me-1"></i>Error al consultar IMSS.</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ── Score Crediticio / Buró ────────────────────────────────────── --}}
    @if($scoreQuery)
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center border-0 pb-0">
                <i class="ri-bank-card-line me-2 text-success fs-18"></i>
                <h6 class="card-title mb-0 fw-bold flex-grow-1">Score Crediticio / Buró de Crédito</h6>
                @if($scoreQuery->status === 'completed' && !empty($scoreData['rango_score']))
                    <span class="badge bg-{{ $scoreColor }} fs-11">{{ $scoreData['rango_score'] }}</span>
                @elseif(in_array($scoreQuery->status, ['pending','processing']))
                    <span class="badge bg-info-subtle text-info fs-11"><span class="spinner-border spinner-border-sm" style="width:10px;height:10px;"></span></span>
                @elseif($scoreQuery->status === 'failed')
                    <span class="badge bg-danger-subtle text-danger fs-11">Error</span>
                @endif
            </div>
            <div class="card-body pt-2 fs-13">
                @if($scoreQuery->status === 'completed' && !empty($scoreData))
                    <div class="text-center mb-3">
                        <span class="display-6 fw-bold text-{{ $scoreColor }}">{{ $scoreData['score_buro'] ?? '—' }}</span>
                        <small class="text-muted d-block fs-12">Score {{ $scoreData['buro'] ?? 'Buró de Crédito' }}</small>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><span class="text-muted d-block fs-11">Nivel de riesgo</span><strong>{{ $scoreData['nivel_riesgo'] ?? '—' }}</strong></div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Cuentas activas</span>{{ $scoreData['cuentas_activas'] ?? '—' }}</div>
                        <div class="col-6"><span class="text-muted d-block fs-11">En mora</span>
                            <span class="badge {{ ($scoreData['cuentas_en_mora'] ?? 0) > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} fs-11">
                                {{ $scoreData['cuentas_en_mora'] ?? 0 }}
                            </span>
                        </div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Monto vencido</span>${{ number_format($scoreData['monto_vencido'] ?? 0, 2) }}</div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Deuda total</span>${{ number_format($scoreData['monto_total_deuda'] ?? 0, 2) }}</div>
                        <div class="col-6"><span class="text-muted d-block fs-11">Consultas recientes</span>{{ $scoreData['consultas_recientes'] ?? '—' }}</div>
                    </div>
                    @if(!empty($scoreData['aviso_legal']))
                    <div class="alert alert-warning border-0 bg-warning-subtle mt-3 mb-0 py-2 px-3 fs-11">
                        <i class="ri-shield-check-line me-1"></i>{{ $scoreData['aviso_legal'] }}
                    </div>
                    @endif
                @elseif($scoreQuery->status === 'failed')
                    <p class="text-danger mb-0 fs-12"><i class="ri-error-warning-line me-1"></i>Error al consultar Buró.</p>
                @elseif(!$subject->credit_consent_granted)
                    <div class="text-center text-muted py-3 fs-12">
                        <i class="ri-lock-line fs-20 d-block mb-2"></i>
                        El investigado no otorgó consentimiento para consultar su historial crediticio.
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>
@endif



{{-- ─────────── TARJETA DENUE: Directorio Empresarial INEGI ─────────── --}}
@if($subject->tipo === 'persona_moral')
@php
    $denueQuery = $queries->firstWhere('source_type', 'denue');
    $denueData  = $denueQuery?->result?->processed_data ?? [];
    $denueEstabs = $denueData['establecimientos'] ?? [];
@endphp

<div class="row mt-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header align-items-center d-flex border-0 pb-0">
                <div class="flex-grow-1">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="ri-store-2-line me-2 text-success"></i>
                        Directorio Empresarial DENUE (INEGI)
                    </h5>
                    <p class="text-muted fs-12 mb-0 mt-1">
                        Registro oficial de establecimientos económicos de México.
                        Fuente: <a href="https://www.inegi.org.mx/app/mapa/denue/" target="_blank" rel="noopener" class="text-muted">INEGI DENUE</a>
                        · <span class="text-success fw-semibold">$0 por consulta</span>
                    </p>
                </div>
                <div class="flex-shrink-0 d-flex align-items-center gap-2">
                    @if($denueQuery)
                        @if($denueQuery->status === 'completed')
                            <span class="badge bg-success fs-11">
                                <i class="ri-store-2-line me-1"></i>
                                {{ $denueData['total_encontrados'] ?? 0 }} registro(s) encontrado(s)
                            </span>
                        @elseif(in_array($denueQuery->status, ['processing','pending']))
                            <span class="badge bg-info-subtle text-info fs-11">
                                <span class="spinner-border spinner-border-sm me-1" style="width:10px;height:10px;"></span> Consultando DENUE…
                            </span>
                        @elseif($denueQuery->status === 'failed')
                            <span class="badge bg-danger-subtle text-danger fs-11">Error</span>
                        @endif
                    @else
                        <span class="badge bg-light text-muted fs-11">Pendiente de ejecución</span>
                    @endif
                </div>
            </div>

            <div class="card-body">
                @if($denueQuery && $denueQuery->status === 'completed' && count($denueEstabs) > 0)
                    @if(!empty($denueData['mensaje']) && str_contains($denueData['mensaje'], '[MOCK]'))
                        <div class="alert alert-info border-0 fs-12 py-2 mb-3">
                            <i class="ri-information-line me-1"></i>
                            {{ $denueData['mensaje'] }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle fs-13">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre / Razón Social</th>
                                    <th>Actividad (SCIAN)</th>
                                    <th>Personal</th>
                                    <th>Domicilio</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($denueEstabs as $e)
                                <tr>
                                    <td>
                                        <span class="fw-semibold d-block">{{ $e['nombre_estab'] ?? '—' }}</span>
                                        @if(!empty($e['razon_social']) && $e['razon_social'] !== $e['nombre_estab'])
                                            <small class="text-muted">{{ $e['razon_social'] }}</small>
                                        @endif
                                        @if(!empty($e['id_denue']))
                                            <small class="text-muted d-block">ID DENUE: {{ $e['id_denue'] }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($e['codigo_act']))
                                            <span class="badge bg-secondary-subtle text-secondary fs-11 me-1">{{ $e['codigo_act'] }}</span>
                                        @endif
                                        <span class="fs-12">{{ $e['actividad'] ?? '—' }}</span>
                                    </td>
                                    <td>
                                        @if(!empty($e['personal_ocupado']))
                                            <span class="badge bg-primary-subtle text-primary fs-11">
                                                <i class="ri-group-line me-1"></i>{{ $e['personal_ocupado'] }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="fs-12">
                                        @php
                                            $dir = collect([
                                                $e['calle'] ?? null,
                                                $e['num_exterior'] ?? null,
                                                $e['colonia'] ?? null,
                                                $e['municipio'] ?? null,
                                                $e['entidad'] ?? null,
                                            ])->filter()->implode(', ');
                                        @endphp
                                        {{ $dir ?: '—' }}
                                        @if(!empty($e['codigo_postal']))
                                            <br><small class="text-muted">C.P. {{ $e['codigo_postal'] }}</small>
                                        @endif
                                        @if(!empty($e['telefono']))
                                            <br><small><i class="ri-phone-line me-1 text-muted"></i>{{ $e['telefono'] }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(!empty($e['latitud']) && !empty($e['longitud']))
                                            <a href="https://www.google.com/maps?q={{ $e['latitud'] }},{{ $e['longitud'] }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="btn btn-soft-success btn-sm"
                                               title="Ver en Google Maps">
                                                <i class="ri-map-pin-2-line"></i>
                                            </a>
                                        @endif
                                        @if(!empty($e['sitio_web']))
                                            <a href="{{ $e['sitio_web'] }}" target="_blank" rel="noopener noreferrer"
                                               class="btn btn-soft-info btn-sm ms-1" title="Sitio web">
                                                <i class="ri-external-link-line"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(($denueData['total_encontrados'] ?? 0) > count($denueEstabs))
                        <p class="text-muted fs-12 text-end mt-2 mb-0">
                            <i class="ri-information-line me-1"></i>
                            Mostrando {{ count($denueEstabs) }} de {{ $denueData['total_encontrados'] }} registros encontrados.
                        </p>
                    @endif

                @elseif($denueQuery && $denueQuery->status === 'completed' && count($denueEstabs) === 0)
                    <div class="text-center py-4 text-muted">
                        <i class="ri-store-2-line fs-24 d-block mb-2"></i>
                        No se encontraron registros en el DENUE para este sujeto.
                        <br><small>{{ $denueData['mensaje'] ?? '' }}</small>
                    </div>

                @elseif($denueQuery && $denueQuery->status === 'failed')
                    <div class="alert alert-danger border-0 fs-13">
                        <i class="ri-error-warning-line me-1"></i>
                        Error al consultar el DENUE.
                    </div>

                @elseif(!$denueQuery)
                    <div class="text-center text-muted py-4 fs-13">
                        <i class="ri-store-2-line fs-24 d-block mb-2 text-muted"></i>
                        La consulta al DENUE se ejecutará al iniciar la investigación del sujeto.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif {{-- /persona_moral DENUE --}}

@endsection



@section('script')
<script>
    let pollInterval = null;

    function startPolling() {
        if (pollInterval) return;
        pollInterval = setInterval(fetchAndRefreshAccordion, 2500);
    }

    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    function fetchAndRefreshAccordion() {
        fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(htmlText => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');

            // 1. Remember currently expanded accordions
            const openIds = Array.from(document.querySelectorAll('#sourcesAccordion .accordion-collapse.show')).map(el => el.id);

            // 2. Replace sourcesAccordion inner HTML
            const newAccordion = doc.getElementById('sourcesAccordion');
            const currentAccordion = document.getElementById('sourcesAccordion');
            if (newAccordion && currentAccordion) {
                currentAccordion.innerHTML = newAccordion.innerHTML;
            }

            // 3. Restore open accordions
            openIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.classList.add('show');
                    const btn = document.querySelector(`button[data-bs-target="#${id}"]`);
                    if (btn) btn.classList.remove('collapsed');
                }
            });

            // 4. Update Re-ejecutar Todo button state
            const newBtn = doc.getElementById('runInvestigationBtn');
            const currentBtn = document.getElementById('runInvestigationBtn');
            if (newBtn && currentBtn) {
                currentBtn.outerHTML = newBtn.outerHTML;
            }

            // 5. Check if any queries are still processing
            const stillProcessing = doc.querySelector('#sourcesAccordion .spinner-border, #sourcesAccordion .badge.bg-warning-subtle') !== null;
            if (!stillProcessing) {
                stopPolling();
            }
        })
        .catch(err => console.error('Error al actualizar acordeón por AJAX:', err));
    }

    // Intercept form submissions for background checks
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.action && form.action.includes('/investigate')) {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalHtml = submitBtn ? submitBtn.innerHTML : '';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando...';
            }

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Start or trigger polling immediately
                    fetchAndRefreshAccordion();
                    startPolling();
                } else {
                    alert(data.message || 'Ocurrió un error al procesar la consulta.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                    }
                }
            })
            .catch(err => {
                console.error('Error AJAX en formulario, ejecutando submit normal:', err);
                form.submit();
            });
        }
    });

    // Auto-start polling on page load if queries are currently processing
    @if($isProcessing)
        startPolling();
    @endif
</script>
@endsection

<script>
function copyEnrollUrl() {
    const input = document.getElementById('enrollUrlInput');
    if (!input) return;
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function () {
        const btn = document.getElementById('btnCopyEnroll');
        if (btn) {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="ri-check-line text-success"></i>';
            setTimeout(() => { btn.innerHTML = original; }, 2000);
        }
    }).catch(function () {
        // Fallback para navegadores sin Clipboard API
        document.execCommand('copy');
    });
}

let currentSavedTier = parseInt({{ $subject->tier_level ?? 1 }});

function selectTierLevel(level) {
    const radio = document.getElementById('tier' + level);
    if (!radio || radio.disabled) return;

    radio.checked = true;

    // Update active tier card styles
    [1, 2, 3, 4].forEach(lvl => {
        const card = document.getElementById('tierCard' + lvl);
        if (!card) return;
        if (lvl === level) {
            card.style.background = 'linear-gradient(135deg, rgba(24, 119, 242, 0.22) 0%, rgba(27, 34, 48, 0.95) 100%)';
            card.style.borderColor = '#1877f2';
            card.style.boxShadow = '0 0 15px rgba(24, 119, 242, 0.35)';
        } else {
            card.style.background = '#1b2230';
            card.style.borderColor = 'rgba(255,255,255,0.12)';
            card.style.boxShadow = 'none';
        }
    });

    filterSourcesByTier(level);
}

function saveTierLevel() {
    const selectedRadio = document.querySelector('input[name="tier_level"]:checked');
    if (!selectedRadio) return;

    const level = parseInt(selectedRadio.value);
    const form = document.getElementById('tierLevelForm');
    if (!form) return;
    const formData = new FormData(form);

    const btnSave = document.getElementById('btnSaveTier');
    if (btnSave) {
        btnSave.disabled = true;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Guardando...';
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content 
                   || document.querySelector('input[name="_token"]')?.value 
                   || '{{ csrf_token() }}';

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async r => {
        const data = await r.json().catch(() => ({}));
        if (btnSave) {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="ri-save-3-line me-1"></i> Guardar Nivel de Investigación';
        }
        if (r.ok && data.success) {
            currentSavedTier = level;
            const badge = document.getElementById('currentTierBadge');
            if (badge) badge.textContent = 'Nivel Guardado: TIER ' + level;
            
            // Show edit button, hide save button
            const btnEdit = document.getElementById('btnEditTier');
            if (btnEdit) btnEdit.classList.remove('d-none');
            if (btnSave) btnSave.classList.add('d-none');

            // Apply section filtering
            filterSourcesByTier(level);
        } else {
            alert(data.error || data.message || 'No se pudo guardar el nivel de investigación.');
        }
    })
    .catch(err => {
        if (btnSave) {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="ri-save-3-line me-1"></i> Guardar Nivel de Investigación';
        }
        console.error(err);
        alert('Error de conexión al guardar el Nivel de Investigación.');
    });
}

function enableTierEdit() {
    const btnSave = document.getElementById('btnSaveTier');
    const btnEdit = document.getElementById('btnEditTier');
    if (btnSave) btnSave.classList.remove('d-none');
    if (btnEdit) btnEdit.classList.add('d-none');
}

function filterSourcesByTier(level) {
    for (let i = 1; i <= 4; i++) {
        const sec = document.getElementById('tierSection' + i);
        if (sec) {
            sec.style.display = (i <= level) ? 'block' : 'none';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    filterSourcesByTier(currentSavedTier);
    if (currentSavedTier > 0) {
        const btnEdit = document.getElementById('btnEditTier');
        const btnSave = document.getElementById('btnSaveTier');
        if (btnEdit) btnEdit.classList.remove('d-none');
        if (btnSave) btnSave.classList.add('d-none');
    }
});
</script>
