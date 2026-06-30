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
                    <li class="breadcrumb-item"><a href="{{ route('tenant.projects.show', $subject->project_id) }}">Proyecto</a></li>
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
    $rfcQuery = $queries->firstWhere('source_type', 'rfc');
    $csdQuery = $queries->firstWhere('source_type', 'csd');
    $sigerQuery = $queries->firstWhere('source_type', 'siger');
    $satListasQuery = $queries->firstWhere('source_type', 'sat_listas');
    $marcasQuery = $queries->firstWhere('source_type', 'marcas');

    $hasCompletedQueries = $queries->where('status', 'completed')->isNotEmpty();
    $isProcessing = $queries->whereIn('status', ['pending', 'processing'])->isNotEmpty();

    // Check SAT list alert
    $hasSatAlert = false;
    if ($satListasQuery && $satListasQuery->status === 'completed' && isset($satListasQuery->result->processed_data['en_lista_69b'])) {
        $hasSatAlert = (bool) $satListasQuery->result->processed_data['en_lista_69b'];
    }
@endphp

@if($hasSatAlert)
<div class="alert alert-danger alert-dismissible fade show border-0 shadow" role="alert">
    <div class="d-flex align-items-center">
        <div class="flex-shrink-0 me-3">
            <i class="ri-error-warning-fill fs-24 align-middle text-danger"></i>
        </div>
        <div class="flex-grow-1">
            <h5 class="alert-heading text-danger fw-semibold">¡ALERTA DE RIESGO CRÍTICO!</h5>
            <p class="mb-0 fs-13">El sujeto <strong>{{ $subject->name_or_company }}</strong> ha sido encontrado en las listas del SAT del artículo <strong>69-B (Facturación Simulada - EFOS/EDOS)</strong> con estatus de <strong>{{ $satListasQuery->result->processed_data['estatus_69b'] ?? 'Presunto' }}</strong>. Proceda con precaución y revise los detalles técnicos de la auditoría.</p>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <!-- Demographic / Details Panel -->
    <div class="col-xl-4">
        <div class="card card-profile">
            <div class="card-body p-4">
                <div class="text-center">
                    <div class="avatar-md mx-auto mb-3">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24 shadow material-shadow">
                            {{ $subject->tipo == 'persona_fisica' ? 'PF' : 'PM' }}
                        </span>
                    </div>
                    <h5 class="fs-16 mb-1">{{ $subject->name_or_company }}</h5>
                    <p class="text-muted mb-4 text-capitalize">{{ str_replace('_', ' ', $subject->tipo) }}</p>
                </div>
                
                <div class="table-responsive table-card">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="fw-semibold" style="width: 30%">RFC:</td>
                                <td><code>{{ $subject->rfc }}</code></td>
                            </tr>
                            @if($subject->curp)
                            <tr>
                                <td class="fw-semibold">CURP:</td>
                                <td><code>{{ $subject->curp }}</code></td>
                            </tr>
                            @endif
                            <tr>
                                <td class="fw-semibold">Domicilio:</td>
                                <td><span class="text-muted">{{ $subject->address ?: 'No provisto' }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr>

                <h6 class="text-uppercase fs-12 mb-3">Consentimiento Legal</h6>
                <div class="table-responsive table-card">
                    <table class="table table-borderless mb-0 fs-13">
                        <tbody>
                            <tr>
                                <td class="fw-semibold" style="width: 30%">Otorgado:</td>
                                <td><span class="badge bg-success-subtle text-success">Sí, Expreso</span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Fecha:</td>
                                <td>{{ $subject->consent_date ? $subject->consent_date->format('d/m/Y H:i') : 'No registrado' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Finalidad:</td>
                                <td><span class="text-muted">{{ $subject->consent_legal_basis }}</span></td>
                            </tr>
                            @if($subject->consent_document_path)
                            <tr>
                                <td class="fw-semibold">Documento:</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary"><i class="ri-checkbox-circle-line"></i> Carta Adjunta</span>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 gap-2 d-flex flex-column">
                    <a href="{{ route('tenant.subjects.edit', $subject->id) }}" class="btn btn-soft-primary w-100"><i class="ri-edit-box-line me-1"></i> Editar Ficha del Sujeto</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Investigation Sources Tabs Panel -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header border-0 align-items-center d-flex">
                <h5 class="card-title mb-0 flex-grow-1">Fuentes de Verificación de Antecedentes</h5>
                <div class="flex-shrink-0 d-flex gap-2">
                    @if($hasCompletedQueries)
                        <a href="{{ route('tenant.subjects.report', $subject->id) }}" target="_blank" class="btn btn-info btn-sm">
                            <i class="ri-file-pdf-line align-bottom me-1"></i> Descargar PDF
                        </a>
                    @endif

                    @if($queries->isEmpty() || $isProcessing)
                    <form action="{{ route('tenant.subjects.investigate', $subject->id) }}" method="POST" id="startInvestigationForm">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm" id="runInvestigationBtn" {{ $isProcessing ? 'disabled' : '' }}>
                            @if($isProcessing)
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Investigando...
                            @else
                                <i class="ri-play-circle-line align-bottom me-1"></i> Iniciar Investigación
                            @endif
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            
            <div class="card-body">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs nav-tabs-custom nav-success nav-justified mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#rfc-tab" role="tab">
                            Validación RFC
                            @if($rfcQuery)
                                <span class="badge rounded-pill bg-{{ $rfcQuery->status === 'completed' ? 'success' : ($rfcQuery->status === 'failed' ? 'danger' : 'warning') }} fs-10 ms-1">{{ $rfcQuery->status }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#csd-tab" role="tab">
                            Sellos CSD
                            @if($csdQuery)
                                <span class="badge rounded-pill bg-{{ $csdQuery->status === 'completed' ? 'success' : ($csdQuery->status === 'failed' ? 'danger' : 'warning') }} fs-10 ms-1">{{ $csdQuery->status }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $subject->tipo === 'persona_fisica' ? 'disabled text-decoration-line-through' : '' }}" data-bs-toggle="tab" href="#siger-tab" role="tab">
                            Registro SIGER
                            @if($sigerQuery)
                                <span class="badge rounded-pill bg-{{ $sigerQuery->status === 'completed' ? 'success' : ($sigerQuery->status === 'failed' ? 'danger' : 'warning') }} fs-10 ms-1">{{ $sigerQuery->status }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#sat69-tab" role="tab">
                            Listas SAT 69/B
                            @if($satListasQuery)
                                <span class="badge rounded-pill bg-{{ $satListasQuery->status === 'completed' ? 'success' : ($satListasQuery->status === 'failed' ? 'danger' : 'warning') }} fs-10 ms-1">{{ $satListasQuery->status }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#impi-tab" role="tab">
                            Marcas IMPI
                            @if($marcasQuery)
                                <span class="badge rounded-pill bg-{{ $marcasQuery->status === 'completed' ? 'success' : ($marcasQuery->status === 'failed' ? 'danger' : 'warning') }} fs-10 ms-1">{{ $marcasQuery->status }}</span>
                            @endif
                        </a>
                    </li>
                </ul>

                <!-- Tab panes -->
                <div class="tab-content text-muted">
                    
                    <!-- VALIDACION RFC TAB -->
                    <div class="tab-pane active" id="rfc-tab" role="tabpanel">
                        @if(!$rfcQuery)
                            <div class="text-center py-4">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:72px;height:72px"></lord-icon>
                                <h5 class="mt-4">Validación del Registro Federal de Contribuyentes</h5>
                                <p class="text-muted">La consulta de validación de este RFC ante el SAT aún no se ha iniciado.</p>
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'rfc']) }}" method="POST" class="mt-3">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-play-circle-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        @elseif($rfcQuery->status === 'pending' || $rfcQuery->status === 'processing')
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                                <h5 class="mt-4">Procesando consulta...</h5>
                                <p class="text-muted">Estamos validando los datos del RFC ante el SAT. Por favor espere...</p>
                            </div>
                        @elseif($rfcQuery->status === 'failed')
                            <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="alert-heading text-danger fw-semibold">Error al consultar la fuente</h6>
                                    <p class="mb-0">{{ $rfcQuery->error_message }}</p>
                                </div>
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'rfc']) }}" method="POST" class="flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        @elseif($rfcQuery->status === 'completed' && $rfcQuery->result)
                            @php $rfcData = $rfcQuery->result->processed_data; @endphp
                            <div class="card border-0">
                                <div class="card-header bg-light-subtle pb-2 border-0 d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 text-primary fw-semibold">Información Oficial SAT</h6>
                                    <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'rfc']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-soft-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                            <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                        </button>
                                    </form>
                                </div>
                                <div class="card-body p-0 pt-2">
                                    <table class="table table-bordered align-middle">
                                        <tbody>
                                            <tr>
                                                <td class="fw-semibold bg-light" style="width: 30%">RFC:</td>
                                                <td><code>{{ $rfcData['rfc'] ?? 'N/A' }}</code></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold bg-light">Válido en SAT:</td>
                                                <td>
                                                    @if($rfcData['valido'] ?? false)
                                                        <span class="badge bg-success-subtle text-success">Sí, Vigente</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">No Válido</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold bg-light">Situación SAT:</td>
                                                <td>
                                                    <span class="badge bg-info-subtle text-info">{{ $rfcData['situacion'] ?? 'ACTIVO' }}</span>
                                                </td>
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
                                                <td class="fw-semibold bg-light">CURP Validado:</td>
                                                <td><code>{{ $rfcData['curp'] }}</code></td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <!-- CSD TAB -->
                    <div class="tab-pane" id="csd-tab" role="tabpanel">
                        @if(!$csdQuery)
                            <div class="text-center py-4">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:72px;height:72px"></lord-icon>
                                <h5 class="mt-4">Certificados de Sello Digital (CSD / FIEL)</h5>
                                <p class="text-muted">La recuperación de certificados vigentes y caducos de este RFC aún no se ha iniciado.</p>
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'csd']) }}" method="POST" class="mt-3">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-play-circle-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        @elseif($csdQuery->status === 'pending' || $csdQuery->status === 'processing')
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                                <h5 class="mt-4">Procesando consulta...</h5>
                                <p class="text-muted">Recuperando el historial de sellos digitales del RFC. Espere...</p>
                            </div>
                        @elseif($csdQuery->status === 'failed')
                            <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="alert-heading text-danger fw-semibold">Error al consultar la fuente</h6>
                                    <p class="mb-0">{{ $csdQuery->error_message }}</p>
                                </div>
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'csd']) }}" method="POST" class="flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        @elseif($csdQuery->status === 'completed' && $csdQuery->result)
                            @php $certs = $csdQuery->result->processed_data['certificados'] ?? []; @endphp
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-primary mb-0 fw-semibold">Historial de Certificados Detectados</h6>
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'csd']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-soft-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                            @if(empty($certs))
                                <div class="text-center text-muted py-3">No se encontraron sellos o firmas electrónicas vinculadas a este RFC.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle">
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
                                                    <span class="badge bg-{{ ($cert['estado'] ?? '') === 'ACTIVO' ? 'success' : 'danger' }}-subtle text-{{ ($cert['estado'] ?? '') === 'ACTIVO' ? 'success' : 'danger' }}">
                                                        {{ $cert['estado'] ?? 'CADUCO' }}
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
                    
                    <!-- SIGER TAB -->
                    <div class="tab-pane" id="siger-tab" role="tabpanel">
                        @if($subject->tipo === 'persona_fisica')
                            <div class="alert alert-warning border-0">
                                <h6 class="alert-heading text-warning fw-semibold"><i class="ri-alert-line align-middle me-1"></i> Fuente Excluida</h6>
                                <p class="mb-0">La consulta del Registro Público de Comercio (SIGER) no aplica para Personas Físicas. Esta fuente está restringida a Sociedades Mercantiles (Personas Morales).</p>
                            </div>
                        @elseif(!$sigerQuery)
                            <div class="text-center py-4">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:72px;height:72px"></lord-icon>
                                <h5 class="mt-4">Registro Público de Comercio (SIGER)</h5>
                                <p class="text-muted">La búsqueda de sociedades mercantiles y participación accionaria aún no se ha iniciado.</p>
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'siger']) }}" method="POST" class="mt-3">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-play-circle-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        @elseif($sigerQuery->status === 'pending' || $sigerQuery->status === 'processing')
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                                <h5 class="mt-4">Procesando consulta...</h5>
                                <p class="text-muted">Buscando actas mercantiles y composiciones de socios ante la Secretaría de Economía. Espere...</p>
                            </div>
                        @elseif($sigerQuery->status === 'failed')
                            <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="alert-heading text-danger fw-semibold">Error al consultar la fuente</h6>
                                    <p class="mb-0">{{ $sigerQuery->error_message }}</p>
                                </div>
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'siger']) }}" method="POST" class="flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        @elseif($sigerQuery->status === 'completed' && $sigerQuery->result)
                            @php $results = $sigerQuery->result->processed_data['resultados'] ?? []; @endphp
                            <div class="d-flex justify-content-end mb-3">
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'siger']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-soft-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                            @if(empty($results))
                                <div class="text-center text-muted py-3">No se localizaron registros comerciales en SIGER vinculados a esta Razón Social.</div>
                            @else
                                @foreach($results as $res)
                                <div class="card border border-dashed mb-3">
                                    <div class="card-header bg-light-subtle align-items-center d-flex pb-2">
                                        <h6 class="card-title mb-0 flex-grow-1 text-primary">Folio Mercantil Electrónico (FME): #{{ $res['fme'] ?? '' }}</h6>
                                        <div class="flex-shrink-0">
                                            <span class="badge bg-success-subtle text-success">{{ $res['entidad_federativa'] ?? 'CDMX' }}</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <div class="col-sm-6">
                                                <span class="text-muted">Fecha de Constitución:</span>
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
                    
                    <!-- LISTAS SAT TAB -->
                    <div class="tab-pane" id="sat69-tab" role="tabpanel">
                        @if(!$satListasQuery)
                            <div class="text-center py-4">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:72px;height:72px"></lord-icon>
                                <h5 class="mt-4">Cumplimiento Fiscal: EFOS / EDOS (Listas 69 y 69-B)</h5>
                                <p class="text-muted">La revisión de listados negros de contribuyentes incumplidos o simuladores aún no se ha iniciado.</p>
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'sat_listas']) }}" method="POST" class="mt-3">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-play-circle-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        @elseif($satListasQuery->status === 'pending' || $satListasQuery->status === 'processing')
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                                <h5 class="mt-4">Procesando consulta...</h5>
                                <p class="text-muted">Buscando reportes negativos o boletines en la lista negra del SAT. Espere...</p>
                            </div>
                        @elseif($satListasQuery->status === 'failed')
                            <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="alert-heading text-danger fw-semibold">Error al consultar la fuente</h6>
                                    <p class="mb-0">{{ $satListasQuery->error_message }}</p>
                                </div>
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'sat_listas']) }}" method="POST" class="flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        @elseif($satListasQuery->status === 'completed' && $satListasQuery->result)
                            @php $listData = $satListasQuery->result->processed_data; @endphp
                            <div class="d-flex justify-content-end mb-3">
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'sat_listas']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-soft-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card border">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-2 text-uppercase fs-11">Lista 69 (Créditos condonados, exigibles o cancelados)</h6>
                                            @if($listData['en_lista_69'] ?? false)
                                                <span class="badge bg-danger-subtle text-danger fs-12 p-2 w-100 text-center"><i class="ri-error-warning-line me-1"></i> Contribuyente Exceptuado</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success fs-12 p-2 w-100 text-center"><i class="ri-checkbox-circle-line me-1"></i> Sin Incidencias Registradas</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card border">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-2 text-uppercase fs-11">Lista 69-B (EFOS - Emisores de Operaciones Simuladas)</h6>
                                            @if($listData['en_lista_69b'] ?? false)
                                                <span class="badge bg-danger-subtle text-danger fs-12 p-2 w-100 text-center"><i class="ri-alert-line me-1"></i> Boletinado Facturación Simulada</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success fs-12 p-2 w-100 text-center"><i class="ri-checkbox-circle-line me-1"></i> Sin Incidencias Registradas</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($listData['en_lista_69b'] ?? false)
                            <div class="card border border-danger border-dashed">
                                <div class="card-header bg-danger-subtle text-danger pb-2">
                                    <h6 class="card-title mb-0 text-danger fw-semibold"><i class="ri-error-warning-fill me-1 align-middle"></i> Detalles del Boletín Oficial</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-semibold bg-light" style="width: 30%">Estatus 69-B:</td>
                                                <td><span class="badge bg-warning text-dark">{{ $listData['estatus_69b'] ?? 'Presunto' }}</span></td>
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
                                </div>
                            </div>
                            @endif
                        @endif
                    </div>
                    
                    <!-- IMPI TAB -->
                    <div class="tab-pane" id="impi-tab" role="tabpanel">
                        @if(!$marcasQuery)
                            <div class="text-center py-4">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:72px;height:72px"></lord-icon>
                                <h5 class="mt-4">Propiedad Industrial (IMPI)</h5>
                                <p class="text-muted">La búsqueda de marcas o patentes registradas a nombre de este sujeto aún no se ha iniciado.</p>
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'marcas']) }}" method="POST" class="mt-3">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-play-circle-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        @elseif($marcasQuery->status === 'pending' || $marcasQuery->status === 'processing')
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                                <h5 class="mt-4">Procesando consulta...</h5>
                                <p class="text-muted">Buscando registros marcarios y denominaciones comerciales en las bases de datos del IMPI. Espere...</p>
                            </div>
                        @elseif($marcasQuery->status === 'failed')
                            <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="alert-heading text-danger fw-semibold">Error al consultar la fuente</h6>
                                    <p class="mb-0">{{ $marcasQuery->error_message }}</p>
                                </div>
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'marcas']) }}" method="POST" class="flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        @elseif($marcasQuery->status === 'completed' && $marcasQuery->result)
                            @php $marcas = $marcasQuery->result->processed_data['marcas'] ?? []; @endphp
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-primary mb-0 fw-semibold">Marcas Registradas / Solicitudes a su Nombre</h6>
                                <form action="{{ route('tenant.subjects.investigate.source', [$subject->id, 'marcas']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-soft-primary" {{ $isProcessing ? 'disabled' : '' }}>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                            @if(empty($marcas))
                                <div class="text-center text-muted py-3">No se detectaron patentes o marcas comerciales registradas ante el IMPI para este titular.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle">
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
    </div>
</div>
@endsection

@if($isProcessing)
@section('script')
<script>
    setTimeout(function() {
        window.location.reload();
    }, 3000);
</script>
@endsection
@endif
