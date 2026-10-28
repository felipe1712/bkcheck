@extends('layouts.master')
@section('title') Expedientes y Monitoreo PLD/EBR @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Tablero de Expedientes PLD/EBR y Monitoreo Periódico (LFPIORPI 2026)</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Expedientes PLD/EBR</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- TARJETAS DE INDICADORES PLD/EBR -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-start border-4 border-primary">
            <div class="card-body">
                <p class="text-uppercase fw-semibold text-muted mb-1 fs-12">Expedientes PLD Vigentes</p>
                <h4 class="fs-22 fw-bold mb-0">
                    {{ $stats['total_expedientes'] ?? 0 }}
                    <span class="fs-13 text-muted fw-normal">/ {{ $stats['total_sujetos'] ?? 0 }} sujetos</span>
                </h4>
                <small class="text-muted">Firmados con hash JCS (RFC 8785)</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-start border-4 border-danger">
            <div class="card-body">
                <p class="text-uppercase fw-semibold text-muted mb-1 fs-12">Nivel de Riesgo de Persona ALTO</p>
                <h4 class="fs-22 fw-bold text-danger mb-0">{{ $stats['alto'] ?? 0 }}</h4>
                <small class="text-muted">Requieren EDD / Reevaluación 3 meses</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-start border-4 border-warning">
            <div class="card-body">
                <p class="text-uppercase fw-semibold text-muted mb-1 fs-12">Nivel MEDIO / BAJO</p>
                <h4 class="fs-22 fw-bold mb-0">
                    <span class="text-warning">{{ $stats['medio'] ?? 0 }} Medio</span> ·
                    <span class="text-success">{{ $stats['bajo'] ?? 0 }} Bajo</span>
                </h4>
                <small class="text-muted">Insumo EBR (RCG Art. 23 Bis 2)</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-start border-4 border-info">
            <div class="card-body">
                <p class="text-uppercase fw-semibold text-muted mb-1 fs-12">Monitoreos Periódicos Activos</p>
                <h4 class="fs-22 fw-bold text-info mb-0">{{ $stats['monitoreos_activos'] ?? 0 }}</h4>
                <small class="text-muted">Reconsulta automática y Webhooks HMAC</small>
            </div>
        </div>
    </div>
</div>

<!-- NOTA METODOLÓGICA LEGAL -->
<div class="alert alert-secondary border-0 shadow-sm mb-3 fs-13">
    <i class="ri-scales-3-line me-1 align-middle fs-16 text-dark"></i>
    <strong>Alcance EBR (LFPIORPI / Acuerdo 115/2026):</strong>
    El <em>Nivel de Riesgo de Persona</em> (<code>BAJO</code>, <code>MEDIO</code>, <code>ALTO</code>) calculado por AvalID es un <strong>insumo determinístico y trazable</strong> basado en las fuentes consultadas; <strong>no sustituye</strong> el <em>Grado de Riesgo</em> de la relación comercial que determina el sujeto obligado integrando además factores transaccionales.
</div>

<!-- TABLA PRINCIPAL DE SUJETOS Y EXPEDIENTES PLD -->
<div class="card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ri-shield-check-line me-1 text-primary"></i> Padrón de Sujetos y Estado de Expedientes PLD/EBR</h5>
        @role('tenant_admin')
        <a href="{{ route('tenant.pld.policy') }}" class="btn btn-sm btn-outline-primary">
            <i class="ri-Equalizer-line align-middle me-1"></i> Configurar Política, Pesos y Webhooks PLD
        </a>
        @endrole
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Sujeto / RFC</th>
                        <th>Proyecto / Finalidad</th>
                        <th class="text-center">Folio Vigente (Versión)</th>
                        <th class="text-center">Score Global</th>
                        <th class="text-center">Nivel de Riesgo Persona (Insumo EBR)</th>
                        <th>Recomendación</th>
                        <th class="text-center">EDD</th>
                        <th class="text-center">Monitoreo Periódico</th>
                        <th class="text-end">Acciones PLD</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $subj)
                        @php
                            $exp = $subj->expedientePld;
                            $sub = $subj->pldSuscripcion;
                            $verCount = $subj->pldExpedienteVersiones->count();
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('tenant.subjects.show', $subj->id) }}" class="fw-semibold text-dark text-decoration-none">
                                    {{ $subj->name_or_company }}
                                </a>
                                <div class="fs-12 text-muted">
                                    <code>{{ $subj->rfc ?: 'Sin RFC' }}</code> ·
                                    <span class="badge bg-light text-dark">{{ $subj->tipo === 'persona_moral' ? 'MORAL' : 'FÍSICA' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="fs-13">{{ $subj->project?->name ?? 'General' }}</div>
                                <small class="text-muted">{{ $exp?->finalidad ?? ($subj->finalidad_clave ?: 'alta_cliente') }}</small>
                            </td>
                            <td class="text-center">
                                @if($exp && $exp->folio_vigente)
                                    <a href="{{ route('tenant.pld.expedientes.show', $subj->id) }}" class="badge bg-primary-subtle text-primary fs-12 text-decoration-none">
                                        {{ $exp->folio_vigente }}
                                    </a>
                                    <div class="fs-11 text-muted mt-1">{{ $verCount }} versión(es)</div>
                                @else
                                    <span class="badge bg-light text-muted">No generado</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($exp)
                                    <span class="fw-bold fs-15">{{ $exp->score_global }}%</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($exp)
                                    @if($exp->nivel_riesgo_persona === 'ALTO')
                                        <span class="badge bg-danger fs-12 px-2 py-1">ALTO</span>
                                    @elseif($exp->nivel_riesgo_persona === 'MEDIO')
                                        <span class="badge bg-warning text-dark fs-12 px-2 py-1">MEDIO</span>
                                    @else
                                        <span class="badge bg-success fs-12 px-2 py-1">BAJO</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($exp)
                                    <code class="fs-11">{{ $exp->recomendacion }}</code>
                                @else
                                    <span class="text-muted fs-12">Pendiente de evaluar</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($exp)
                                    @if($exp->edd_recomendada)
                                        <span class="badge bg-danger-subtle text-danger">SÍ</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">NO</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($sub && $sub->estado === 'ACTIVA')
                                    <span class="badge bg-success-subtle text-success">ACTIVO ({{ $sub->intervalo_meses }}m)</span>
                                    <div class="fs-11 text-muted">Próx: {{ $sub->proxima_reevaluacion?->format('d/m/Y') ?? 'N/A' }}</div>
                                @elseif($sub && $sub->estado === 'BAJA')
                                    <span class="badge bg-secondary-subtle text-secondary">BAJA</span>
                                @else
                                    <span class="badge bg-light text-muted">Sin suscripción</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                    <form method="POST" action="{{ route('tenant.subjects.pld-expediente.generate', $subj->id) }}">
                                        @csrf
                                        <input type="hidden" name="tipo_ejecucion" value="{{ $exp ? 'BAJO_DEMANDA' : 'INICIAL' }}">
                                        <button type="submit" class="btn btn-sm {{ $exp ? 'btn-soft-primary' : 'btn-primary' }}" title="{{ $exp ? 'Ejecutar nueva versión inmutable v' . ($exp->version_vigente + 1) : 'Generar v1 del Expediente PLD/EBR' }}">
                                            <i class="ri-refresh-line align-middle"></i> {{ $exp ? 'Reevaluar (v' . ($exp->version_vigente + 1) . ')' : 'Generar v1' }}
                                        </button>
                                    </form>

                                    @if($exp && $exp->folio_vigente)
                                        <a href="{{ route('tenant.pld.expedientes.show', $subj->id) }}" class="btn btn-sm btn-info" title="Ver Expediente PLD, Historial de Versiones y Diff">
                                            <i class="ri-eye-line align-middle"></i> Ver / Diff
                                        </a>
                                        <a href="{{ route('tenant.pld.expediente.pdf', $exp->folio_vigente) }}" class="btn btn-sm btn-soft-danger" title="Descargar PDF del Expediente PLD">
                                            <i class="ri-file-pdf-line align-middle"></i> PDF
                                        </a>
                                        <a href="{{ route('tenant.pld.expediente.json', $exp->folio_vigente) }}" class="btn btn-sm btn-soft-dark" title="Descargar JSON Canónico Firmado JCS">
                                            <i class="ri-braces-line align-middle"></i> JSON
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No hay sujetos registrados aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
