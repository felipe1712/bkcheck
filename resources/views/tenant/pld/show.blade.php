@extends('layouts.master')
@section('title') Expediente PLD/EBR: {{ $subject->name_or_company }} @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Expediente PLD / EBR, Versionado Inmutable y Monitoreo</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.pld.expedientes.index') }}">Expedientes PLD</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tenant.subjects.show', $subject->id) }}">{{ $subject->name_or_company }}</a></li>
                    <li class="breadcrumb-item active">{{ $activeVersion?->folio ?? 'Sin Versión' }}</li>
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

<!-- BARRA SUPERIOR DE CONTROL: GENERAR NUEVA VERSIÓN, MONITOREO PERIÓDICO Y DESCARGAS -->
<div class="card border-primary border-opacity-25 mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="mb-1 fw-bold">
                {{ $subject->name_or_company }}
                <span class="badge bg-secondary ms-1">{{ $subject->tipo === 'persona_moral' ? 'MORAL' : 'FÍSICA' }}</span>
            </h5>
            <div class="text-muted fs-13">
                RFC: <code>{{ $subject->rfc ?: 'No provisto' }}</code> ·
                CURP: <code>{{ $subject->curp ?: 'No aplica / No provisto' }}</code> ·
                <a href="{{ route('tenant.subjects.show', $subject->id) }}" class="text-primary">Ir al expediente técnico de conectores &rarr;</a>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <!-- Botón Generar / Reevaluar -->
            <form method="POST" action="{{ route('tenant.subjects.pld-expediente.generate', $subject->id) }}">
                @csrf
                <input type="hidden" name="tipo_ejecucion" value="{{ $activeVersion ? 'BAJO_DEMANDA' : 'INICIAL' }}">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ri-refresh-line align-middle me-1"></i>
                    {{ $activeVersion ? 'Ejecutar Nueva Versión Inmutable (v' . ($versions->max('version') + 1) . ')' : 'Generar Expediente PLD Inicial (v1)' }}
                </button>
            </form>

            <!-- Botón Monitoreo Periódico -->
            @php $sub = $subject->pldSuscripcion; @endphp
            @if($sub && $sub->estado === 'ACTIVA')
                <form method="POST" action="{{ route('tenant.pld.expedientes.monitoring', $subject->id) }}">
                    @csrf
                    <input type="hidden" name="accion" value="baja">
                    <input type="hidden" name="motivo" value="relacion_terminada">
                    <button type="submit" class="btn btn-soft-warning btn-sm" title="Próxima reevaluación: {{ $sub->proxima_reevaluacion?->format('d/m/Y') }}">
                        <i class="ri-stop-circle-line align-middle me-1"></i> Dar de Baja Monitoreo (Activo {{ $sub->intervalo_meses }}m)
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('tenant.pld.expedientes.monitoring', $subject->id) }}">
                    @csrf
                    <input type="hidden" name="accion" value="activar">
                    <input type="hidden" name="intervalo_meses" value="{{ ($exp['nivel_riesgo_persona']['nivel'] ?? '') === 'ALTO' ? 3 : 6 }}">
                    <button type="submit" class="btn btn-soft-success btn-sm">
                        <i class="ri-radar-line align-middle me-1"></i> Activar Monitoreo Periódico
                    </button>
                </form>
            @endif

            @if($activeVersion)
                <a href="{{ route('tenant.pld.expediente.pdf', $activeVersion->folio) }}" class="btn btn-danger btn-sm">
                    <i class="ri-file-pdf-line align-middle me-1"></i> Descargar PDF ({{ $activeVersion->folio }})
                </a>
                <a href="{{ route('tenant.pld.expediente.json', $activeVersion->folio) }}" class="btn btn-dark btn-sm">
                    <i class="ri-braces-line align-middle me-1"></i> Descargar JSON Firmado (JCS)
                </a>
            @endif
        </div>
    </div>
</div>

@if(!$exp)
    <div class="alert alert-info">
        Aún no se ha generado una versión del Expediente PLD/EBR para esta persona. Haga clic en <strong>Generar Expediente PLD Inicial (v1)</strong> arriba para evaluar determinísticamente los resultados de sus conectores.
    </div>
@else
    <div class="row">
        <!-- COLUMNA IZQUIERDA: HISTORIAL DE VERSIONES INMUTABLES Y COMPARADOR DIFF -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="card-title text-white mb-0"><i class="ri-git-commit-line me-1"></i> Cadena Inmutable de Versiones ({{ $versions->count() }})</h6>
                    <span class="badge bg-secondary">JCS RFC 8785</span>
                </div>
                <div class="card-body p-2">
                    <div class="list-group list-group-flush">
                        @foreach($versions as $ver)
                            @php $vDecoded = json_decode($ver->payload_canonico, true) ?: []; @endphp
                            <a href="{{ route('tenant.pld.expedientes.show', ['id' => $subject->id, 'folio' => $ver->folio]) }}"
                               class="list-group-item list-group-item-action {{ $activeVersion->folio === $ver->folio ? 'active' : '' }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>{{ $ver->folio }}</strong>
                                    <span class="badge {{ ($vDecoded['nivel_riesgo_persona']['nivel'] ?? '') === 'ALTO' ? 'bg-danger' : (($vDecoded['nivel_riesgo_persona']['nivel'] ?? '') === 'MEDIO' ? 'bg-warning text-dark' : 'bg-success') }}">
                                        {{ $vDecoded['nivel_riesgo_persona']['nivel'] ?? 'MEDIO' }} ({{ $vDecoded['score_global'] ?? 0 }}%)
                                    </span>
                                </div>
                                <div class="fs-11 mt-1 {{ $activeVersion->folio === $ver->folio ? 'text-white-50' : 'text-muted' }}">
                                    {{ $ver->tipo_ejecucion }} · {{ $ver->fecha_generacion?->format('d/m/Y H:i') }}
                                </div>
                                <div class="fs-11 font-monospace text-truncate mt-1 {{ $activeVersion->folio === $ver->folio ? 'text-white-50' : 'text-muted' }}" title="SHA-256: {{ $ver->hash_version }}">
                                    SHA-256: {{ substr($ver->hash_version, 0, 20) }}...
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- COMPARADOR DIFF ENTRE VERSIONES -->
            @if($versions->count() > 1)
                <div class="card border-info">
                    <div class="card-header bg-soft-info">
                        <h6 class="card-title mb-0"><i class="ri-git-Compare-line me-1"></i> Comparador Diff Estructurado</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('tenant.pld.expedientes.show', $subject->id) }}" class="mb-3">
                            <input type="hidden" name="folio" value="{{ $activeVersion->folio }}">
                            <label class="form-label fs-12 fw-semibold">Comparar <code>{{ $activeVersion->folio }}</code> contra:</label>
                            <div class="input-group input-group-sm">
                                <select name="contra" class="form-select">
                                    @foreach($versions as $vOpt)
                                        @if($vOpt->folio !== $activeVersion->folio)
                                            <option value="{{ $vOpt->folio }}" @selected(($diff['version_previa'] ?? '') === $vOpt->folio)>
                                                {{ $vOpt->folio }} ({{ $vOpt->fecha_generacion?->format('d/m/Y') }})
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-info">Comparar</button>
                            </div>
                        </form>

                        @if($diff)
                            <div class="fs-12 mb-2">
                                <strong>Resultado ({{ $diff['version_previa'] ?? 'N/A' }} &rarr; {{ $diff['version_actual'] ?? 'N/A' }}):</strong>
                                @if(!empty($diff['cambios']))
                                    <span class="badge bg-warning text-dark">{{ count($diff['items'] ?? []) }} cambio(s) detectado(s)</span>
                                @else
                                    <span class="badge bg-success">Sin cambios sustantivos (cambios: false)</span>
                                @endif
                            </div>

                            @if(!empty($diff['items']))
                                <ul class="list-group list-group-flush fs-12">
                                    @foreach($diff['items'] as $dItem)
                                        <li class="list-group-item px-0">
                                            <code>{{ $dItem['campo'] }}</code><br>
                                            <span class="text-danger">Antes: {{ is_scalar($dItem['antes']) ? $dItem['antes'] : json_encode($dItem['antes']) }}</span><br>
                                            <span class="text-success fw-semibold">Después: {{ is_scalar($dItem['despues']) ? $dItem['despues'] : json_encode($dItem['despues']) }}</span>
                                            <div class="text-muted fs-11">Fuente: {{ $dItem['fuente'] ?? 'N/A' }}</div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- COLUMNA DERECHA: CONTENIDO ESTRUCTURADO DEL EXPEDIENTE PLD/EBR (12 SECCIONES) -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        Folio <code>{{ $exp['folio'] }}</code> · Versión v{{ $exp['version'] }} ({{ $exp['tipo_ejecucion'] }})
                    </h5>
                    <span class="badge {{ ($exp['nivel_riesgo_persona']['nivel'] ?? '') === 'ALTO' ? 'bg-danger' : (($exp['nivel_riesgo_persona']['nivel'] ?? '') === 'MEDIO' ? 'bg-warning text-dark' : 'bg-success') }} fs-13">
                        NIVEL DE RIESGO PERSONA: {{ $exp['nivel_riesgo_persona']['nivel'] ?? 'MEDIO' }}
                    </span>
                </div>
                <div class="card-body">
                    <!-- SECCIÓN 2 Y 3: RECOMENDACIÓN Y MULTÍMETRO -->
                    <div class="alert {{ ($exp['recomendacion'] ?? '') === 'NO_PROCEDER' ? 'alert-danger' : (($exp['recomendacion'] ?? '') === 'REVISION_MANUAL' ? 'alert-warning' : 'alert-success') }} mb-3">
                        <div class="fw-bold">RECOMENDACIÓN DE ACCIÓN: <code>{{ $exp['recomendacion'] }}</code></div>
                        <div class="fs-13">{{ $exp['recomendacion_justificacion'] ?? '' }}</div>
                    </div>

                    <div class="row text-center mb-3 g-2">
                        <div class="col-md-2">
                            <div class="p-2 border rounded bg-light">
                                <div class="fs-11 text-muted">SCORE GLOBAL</div>
                                <div class="fs-20 fw-bold">{{ $exp['score_global'] }}%</div>
                            </div>
                        </div>
                        @foreach(['IDENTIDAD', 'PLD', 'CONSISTENCIA', 'REPUTACIONAL'] as $cat)
                            @php $cData = $exp['scores_categoria'][$cat] ?? ['score' => 0, 'peso' => 25, 'estatus' => 'N/A']; @endphp
                            <div class="col-md-2">
                                <div class="p-2 border rounded">
                                    <div class="fs-11 text-muted">{{ $cat }} ({{ $cData['peso'] }}%)</div>
                                    <div class="fs-18 fw-bold">{{ $cData['score'] }}%</div>
                                    <div class="fs-10 text-muted">{{ $cData['estatus'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- SECCIÓN 4: INSUMO EBR, PEP, EDD Y FACTORES INHERENTES -->
                    <h6 class="fw-bold border-bottom pb-1 mt-3">4. NIVEL DE RIESGO DE PERSONA (INSUMO EBR — RCG Art. 23 Bis 2)</h6>
                    <ul class="fs-13 mb-2">
                        <li><strong>Debida Diligencia Reforzada (EDD) recomendada:</strong> <code>{{ !empty($exp['edd']['recomendada']) ? 'SÍ' : 'NO' }}</code> — {{ $exp['edd']['causa'] ?? '' }}</li>
                        <li><strong>Estatus PEP:</strong> <code>{{ $exp['pep']['estatus'] ?? 'PENDIENTE' }}</code> (Fuente: {{ $exp['pep']['fuente'] ?? 'N/A' }}) · <strong>Asimilados a PEP:</strong> {{ $exp['pep']['asimilados'] ?? 'no evaluado' }}</li>
                        <li><strong>Reevaluar antes de:</strong> <code>{{ $exp['proxima_reevaluacion'] ?? 'N/A' }}</code> (Intervalo: {{ $exp['intervalo_reevaluacion_meses'] ?? 6 }} meses) · <strong>Conservar hasta:</strong> <code>{{ $exp['conservar_hasta'] ?? 'N/A' }}</code></li>
                    </ul>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered fs-12 mb-1">
                            <thead class="table-light">
                                <tr>
                                    <th>Factor Inherente (RCG Art. 23 Bis 2)</th>
                                    <th>Valor</th>
                                    <th>Fuente Exacta</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exp['factores_inherentes'] ?? [] as $fi)
                                    <tr>
                                        <td class="fw-semibold">{{ $fi['factor'] }}</td>
                                        <td>{{ $fi['valor'] }}</td>
                                        <td><code>{{ $fi['fuente'] }}</code></td>
                                        <td>
                                            <span class="badge {{ $fi['estado'] === 'Evaluado' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $fi['estado'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="fs-11 text-muted">{{ $exp['factores_transaccionales']['leyenda_fija'] ?? '' }}</div>
                    </div>

                    <!-- SECCIÓN 7: HALLAZGOS CONFIRMADOS -->
                    <h6 class="fw-bold border-bottom pb-1 mt-3">7. HALLAZGOS CONFIRMADOS ({{ count($exp['hallazgos'] ?? []) }})</h6>
                    @if(!empty($exp['hallazgos']))
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered fs-12">
                                <thead class="table-light">
                                    <tr>
                                        <th>Categoría</th>
                                        <th>Fuente y Campo JSON</th>
                                        <th>Descripción Trazable</th>
                                        <th>Efecto en Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($exp['hallazgos'] as $h)
                                        <tr>
                                            <td><span class="badge bg-danger">{{ $h['categoria'] }}</span></td>
                                            <td><code>{{ $h['fuente'] }}</code> &rarr; <code>{{ $h['campo'] }}</code></td>
                                            <td>{{ $h['descripcion'] }}</td>
                                            <td><code>{{ $h['efecto_score'] ?? '' }}</code></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="fs-13 text-muted">Sin hallazgos adversos confirmados en las fuentes consultadas.</p>
                    @endif

                    <!-- SECCIÓN 8: FUENTES PENDIENTES DE VERIFICACIÓN -->
                    @if(!empty($exp['fuentes_pendientes']))
                        <h6 class="fw-bold border-bottom pb-1 mt-3 text-warning">8. FUENTES PENDIENTES DE VERIFICACIÓN ({{ count($exp['fuentes_pendientes']) }})</h6>
                        <ul class="fs-12">
                            @foreach($exp['fuentes_pendientes'] as $fp)
                                <li>
                                    <code>{{ $fp['fuente'] }}</code> ({{ $fp['categoria'] }} / Criticidad {{ $fp['criticidad'] }}) —
                                    Estado: <strong>{{ $fp['estado'] }}</strong> (HTTP {{ $fp['status_http'] }}) — {{ $fp['motivo'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <!-- SECCIÓN 5 Y 12: CONTROL DE VERSIÓN Y TEXTO CANÓNICO COMPLETO -->
                    <h6 class="fw-bold border-bottom pb-1 mt-3">5. CADENA CRIPTOGRÁFICA Y TEXTO ÍNTEGRO DEL EXPEDIENTE (§9)</h6>
                    <div class="bg-light p-2 rounded border font-monospace fs-11 mb-2">
                        <div><strong>Hash Versión Actual (JCS SHA-256):</strong> {{ $exp['hash_version'] }}</div>
                        <div><strong>Hash Versión Previa:</strong> {{ $exp['hash_previo'] ?? 'N/A (Versión Inicial v1)' }}</div>
                    </div>
                    <pre class="bg-dark text-light p-3 rounded fs-11" style="max-height: 360px; overflow-y: auto; white-space: pre-wrap;">{{ $activeVersion->expediente_formateado_texto }}</pre>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
