@extends('layouts.master')
@section('title') Política de Puntuación PLD/EBR @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Política de Puntuación, API y Webhooks PLD/EBR (Tenant Admin)</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.pld.expedientes.index') }}">Expedientes PLD</a></li>
                    <li class="breadcrumb-item active">Política y Webhooks PLD</li>
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
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- BLOQUE 1: MARCO NORMATIVO FIJO (NO EDITABLE) VS CREDENCIALES API Y WEBHOOKS -->
<div class="row">
    <div class="col-lg-6">
        <div class="card border-secondary">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title text-white mb-0">Parámetros Exigidos por Norma (No Editables)</h5>
                <span class="badge bg-secondary">LFPIORPI / RCG 115/2026</span>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Clasificación mínima de riesgo:</strong> Al menos 3 niveles (<code>BAJO</code>, <code>MEDIO</code>, <code>ALTO</code>).
                            <br><small class="text-muted">Fuente legal: RCG Art. 23 Bis 2, fracción II. AvalID calcula el <em>Nivel de Riesgo de Persona</em> como insumo; el <em>Grado de Riesgo</em> de la relación corresponde exclusivamente al sujeto obligado.</small>
                        </div>
                        <span class="badge bg-dark">NORMA FIJA</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Techo máximo de reevaluación:</strong> Al menos cada <strong>6 meses</strong> (y revisión anual de expediente de identificación).
                            <br><small class="text-muted">Fuente legal: RCG Art. 23 Bis 1 y Art. 21.</small>
                        </div>
                        <span class="badge bg-dark">NORMA FIJA</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Conservación documental:</strong> Mínimo <strong>10 años</strong>.
                            <br><small class="text-muted">Fuente legal: LFPIORPI Art. 18 fr. IV y Reglamento Art. 20.</small>
                        </div>
                        <span class="badge bg-dark">NORMA FIJA</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Prelación de Beneficiario Controlador (Persona Moral):</strong> (I) ≥ 25% capital social → (II) control por otros medios → (III) alta dirección.
                            <br><small class="text-muted">Fuente legal: RCG Art. 23 Quinquies.</small>
                        </div>
                        <span class="badge bg-dark">NORMA FIJA</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title text-white mb-0">Credenciales API y Configuración de Webhooks HMAC</h5>
                <span class="badge bg-light text-primary">M9 / M10</span>
            </div>
            <div class="card-body">
                <label class="form-label fw-bold">API Key de Tenant (Header <code>X-Tenant-Api-Key</code>):</label>
                <div class="input-group mb-3">
                    <input type="text" class="form-control font-monospace" value="{{ $tenant->api_key }}" readonly>
                    <form method="POST" action="{{ route('tenant.pld.policy.rotate-key') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">Rotar Key</button>
                    </form>
                </div>

                <hr>
                <h6 class="fw-bold mb-2"><i class="ri-webhook-line me-1"></i> Registrar / Actualizar Endpoint de Webhook</h6>
                <form method="POST" action="{{ route('tenant.pld.policy.webhook') }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-7">
                            <label class="form-label fs-12 mb-1">URL HTTPS receptora</label>
                            <input type="url" name="url" class="form-control form-control-sm" value="{{ $webhooks->first()?->url }}" placeholder="https://su-sistema.com/webhooks/avalid" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fs-12 mb-1">Secreto HMAC-SHA256 (mín. 16 chars)</label>
                            <input type="password" name="secreto" class="form-control form-control-sm" placeholder="whsec_****************" minlength="16" required>
                        </div>
                        <div class="col-12 mt-2">
                            <label class="form-label fs-12 mb-1 d-block">Eventos Suscritos:</label>
                            @php $currEvts = $webhooks->first()?->eventos_suscritos ?? ['HALLAZGO_CRITICO', 'NIVEL_CAMBIO', 'RECOMENDACION_CAMBIO', 'FUENTE_CAIDA_PERSISTENTE', 'REEVALUACION_VENCIDA']; @endphp
                            <div class="d-flex flex-wrap gap-3 fs-12">
                                @foreach(['HALLAZGO_CRITICO', 'NIVEL_CAMBIO', 'RECOMENDACION_CAMBIO', 'FUENTE_CAIDA_PERSISTENTE', 'REEVALUACION_VENCIDA'] as $evName)
                                    <label class="form-check-label">
                                        <input type="checkbox" name="eventos_suscritos[]" value="{{ $evName }}" class="form-check-input me-1" @checked(in_array($evName, $currEvts, true) || in_array('*', $currEvts, true))>
                                        <code>{{ $evName }}</code>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center mt-2">
                            <label class="form-check-label fs-12">
                                <input type="checkbox" name="incluir_sin_cambios" value="1" class="form-check-input me-1" @checked($webhooks->first()?->incluir_sin_cambios)>
                                Notificar también <code>REEVALUACION_SIN_CAMBIOS</code>
                            </label>
                            <button type="submit" class="btn btn-sm btn-primary">Guardar Webhook</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- BLOQUE 2: PARÁMETROS DE POLÍTICA AVALID / TENANT (EDITABLES) -->
<form method="POST" action="{{ route('tenant.pld.policy.update') }}">
    @csrf
    <div class="card border-info">
        <div class="card-header bg-soft-info d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Parámetros de Política Interna (Editables por Tenant Admin)</h5>
            <span class="badge bg-info">POLÍTICA AVALID / TENANT (NO ES LEY)</span>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Aviso metodológico:</strong> Los pesos porcentuales, los topes de puntuación por regla de disparo y el acortamiento del intervalo para nivel ALTO (3 meses) son parámetros de la política de riesgo de su organización configurada en AvalID, <strong>no</strong> valores fijos de la LFPIORPI.
            </div>

            @php
                $pId = $policies->get('IDENTIDAD');
                $intBM = $pId?->intervalo_reevaluacion_bajo_medio_meses ?? 6;
                $intAlto = $pId?->intervalo_reevaluacion_alto_meses ?? 3;
            @endphp

            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Peso IDENTIDAD (%)</label>
                    <input type="number" name="pesos[IDENTIDAD]" class="form-control" min="0" max="100" value="{{ $policies->get('IDENTIDAD')?->peso_porcentual ?? 40 }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Peso CUMPLIMIENTO PLD (%)</label>
                    <input type="number" name="pesos[PLD]" class="form-control" min="0" max="100" value="{{ $policies->get('PLD')?->peso_porcentual ?? 30 }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Peso CONSISTENCIA (%)</label>
                    <input type="number" name="pesos[CONSISTENCIA]" class="form-control" min="0" max="100" value="{{ $policies->get('CONSISTENCIA')?->peso_porcentual ?? 15 }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Peso REPUTACIONAL / OSINT (%)</label>
                    <input type="number" name="pesos[REPUTACIONAL]" class="form-control" min="0" max="100" value="{{ $policies->get('REPUTACIONAL')?->peso_porcentual ?? 15 }}" required>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Intervalo Reevaluación BAJO y MEDIO (meses, máx. legal 6)</label>
                    <input type="number" name="intervalo_bajo_medio" class="form-control" min="1" max="6" value="{{ $intBM }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Intervalo Reevaluación ALTO (meses, máx. legal 6 — política sugerida: 3)</label>
                    <input type="number" name="intervalo_alto" class="form-control" min="1" max="6" value="{{ $intAlto }}" required>
                </div>
            </div>

            <h6 class="fw-bold mt-3">Topes y Penalizaciones por Regla de Disparo (Sección 4)</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Categoría</th>
                            <th>Clave Regla</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th>Valor (Tope / Pts)</th>
                            <th>Activa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['IDENTIDAD', 'PLD', 'CONSISTENCIA', 'REPUTACIONAL'] as $cat)
                            @php $triggers = $policies->get($cat)?->triggers_config ?? []; @endphp
                            @foreach($triggers as $tKey => $tCfg)
                            <tr>
                                <td><span class="badge bg-secondary">{{ $cat }}</span></td>
                                <td><code>{{ $tKey }}</code></td>
                                <td>{{ $tCfg['etiqueta'] ?? $tKey }}</td>
                                <td><code>{{ strtoupper($tCfg['tipo'] ?? 'tope_categoria') }}</code></td>
                                <td style="width: 130px;">
                                    <input type="number" name="triggers[{{ $cat }}][{{ $tKey }}][valor]" class="form-control form-control-sm" min="0" max="100" value="{{ $tCfg['valor'] ?? 0 }}">
                                </td>
                                <td class="text-center" style="width: 80px;">
                                    <input type="checkbox" name="triggers[{{ $cat }}][{{ $tKey }}][activo]" value="1" class="form-check-input" @checked(!empty($tCfg['activo']))>
                                </td>
                            </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">Guardar Política PLD/EBR del Tenant</button>
            </div>
        </div>
    </div>
</form>

<!-- BLOQUE 3: BITÁCORA RECIENTE DE EVENTOS WEBHOOK EMITIDOS -->
<div class="card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ri-notification-badge-line me-1 text-primary"></i> Bitácora de Eventos PLD / Webhooks Emitidos (Sin PII)</h5>
        <span class="badge bg-secondary">GET /api/eventos</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle fs-12 mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID Evento</th>
                        <th>Tipo</th>
                        <th>Folio Origen</th>
                        <th>Nivel Previo &rarr; Actual</th>
                        <th>Fecha</th>
                        <th class="text-center">Estado Entrega</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentEventos ?? [] as $evt)
                        <tr>
                            <td><code>{{ $evt->evento_id }}</code></td>
                            <td><span class="badge bg-dark">{{ $evt->tipo }}</span></td>
                            <td><code>{{ $evt->folio_origen }}</code></td>
                            <td>
                                {{ $evt->payload_webhook['nivel_previo'] ?? 'N/A' }} &rarr;
                                <strong>{{ $evt->payload_webhook['nivel_actual'] ?? 'N/A' }}</strong>
                            </td>
                            <td>{{ $evt->fecha?->format('d/m/Y H:i:s') }}</td>
                            <td class="text-center">
                                @if($evt->entregado_en)
                                    <span class="badge bg-success-subtle text-success">Entregado ({{ $evt->entregado_en->format('H:i:s') }})</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Pendiente ({{ $evt->intentos }} int.)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Sin eventos de cambio o hallazgos críticos emitidos aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
