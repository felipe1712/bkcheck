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
                            @php
                                $currEvts = $webhooks->first()?->eventos_suscritos ?? ['HALLAZGO_CRITICO', 'NIVEL_CAMBIO', 'RECOMENDACION_CAMBIO', 'FUENTE_CAIDA_PERSISTENTE', 'REEVALUACION_VENCIDA'];
                                $evtLabels = [
                                    'HALLAZGO_CRITICO'        => 'Hallazgo Crítico (PEP, Sanción, 69-B)',
                                    'NIVEL_CAMBIO'            => 'Cambio de Nivel de Riesgo',
                                    'RECOMENDACION_CAMBIO'    => 'Cambio de Recomendación',
                                    'FUENTE_CAIDA_PERSISTENTE'=> 'Fuente Caída Persistente (3 fallos)',
                                    'REEVALUACION_VENCIDA'    => 'Reevaluación Periódica Vencida',
                                ];
                            @endphp
                            <div class="d-flex flex-wrap gap-3 fs-12">
                                @foreach($evtLabels as $evKey => $evText)
                                    <label class="form-check-label">
                                        <input type="checkbox" name="eventos_suscritos[]" value="{{ $evKey }}" class="form-check-input me-1" @checked(in_array($evKey, $currEvts, true) || in_array('*', $currEvts, true))>
                                        <span>{{ $evText }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center mt-2">
                            <label class="form-check-label fs-12">
                                <input type="checkbox" name="incluir_sin_cambios" value="1" class="form-check-input me-1" @checked($webhooks->first()?->incluir_sin_cambios)>
                                Notificar también cuando una reevaluación periódica concluya sin cambios
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
            <div class="alert alert-info mb-4">
                <strong>Aviso metodológico:</strong> Los pesos porcentuales, los topes de puntuación por regla de evaluación y el acortamiento del intervalo para nivel ALTO (3 meses) son parámetros de la política de riesgo de su organización configurada en AvalID, <strong>no</strong> valores fijos de la LFPIORPI.
            </div>

            @php
                $pId = $policies->get('IDENTIDAD');
                $intBM = $pId?->intervalo_reevaluacion_bajo_medio_meses ?? 6;
                $intAlto = $pId?->intervalo_reevaluacion_alto_meses ?? 3;
                $defaultsCatalog = config('pld.policy_defaults', []);
                $categoryDisplayNames = [
                    'IDENTIDAD'    => ['titulo' => 'Identidad y Biometría',         'badge' => 'bg-primary'],
                    'PLD'          => ['titulo' => 'Cumplimiento PLD y Fiscal',     'badge' => 'bg-danger'],
                    'CONSISTENCIA' => ['titulo' => 'Consistencia Documental',       'badge' => 'bg-warning text-dark'],
                    'REPUTACIONAL' => ['titulo' => 'Reputacional y Judicial',       'badge' => 'bg-info text-dark'],
                ];
            @endphp

            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Peso Identidad y Biometría (%)</label>
                    <input type="number" name="pesos[IDENTIDAD]" class="form-control" min="0" max="100" value="{{ $policies->get('IDENTIDAD')?->peso_porcentual ?? 40 }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Peso Cumplimiento PLD y Fiscal (%)</label>
                    <input type="number" name="pesos[PLD]" class="form-control" min="0" max="100" value="{{ $policies->get('PLD')?->peso_porcentual ?? 30 }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Peso Consistencia Documental (%)</label>
                    <input type="number" name="pesos[CONSISTENCIA]" class="form-control" min="0" max="100" value="{{ $policies->get('CONSISTENCIA')?->peso_porcentual ?? 15 }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Peso Reputacional y Judicial (%)</label>
                    <input type="number" name="pesos[REPUTACIONAL]" class="form-control" min="0" max="100" value="{{ $policies->get('REPUTACIONAL')?->peso_porcentual ?? 15 }}" required>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Intervalo de Reevaluación para Nivel BAJO y MEDIO (meses, máx. legal 6)</label>
                    <input type="number" name="intervalo_bajo_medio" class="form-control" min="1" max="6" value="{{ $intBM }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Intervalo de Reevaluación para Nivel ALTO (meses, máx. legal 6 — política sugerida: 3)</label>
                    <input type="number" name="intervalo_alto" class="form-control" min="1" max="6" value="{{ $intAlto }}" required>
                </div>
            </div>

            <!-- EXPLICACIÓN DE LA TABLA DE REGLAS Y TOPES -->
            <div class="p-3 bg-light rounded border mb-3">
                <h6 class="fw-bold text-dark mb-2">
                    <i class="ri-information-line text-primary align-middle me-1"></i>
                    ¿Cómo funcionan los Topes y Deducciones por Regla de Evaluación?
                </h6>
                <p class="fs-13 text-muted mb-2">
                    Cada una de las 4 categorías inicia en <strong>100 puntos</strong>. Para evitar que un hallazgo crítico (como aparecer en el listado 69-B del SAT, listas OFAC o reprobar biometría facial) se diluya al promediarse con otras fuentes sin hallazgos, el motor aplica dos mecanismos determinísticos:
                </p>
                <div class="row g-2 fs-12">
                    <div class="col-md-6">
                        <div class="p-2 bg-white rounded border h-100">
                            <span class="badge bg-danger-subtle text-danger mb-1">Tope Máximo de Categoría</span>
                            <div class="text-muted">
                                Fija el <strong>límite máximo</strong> que puede alcanzar la categoría cuando ocurre el hallazgo. Por ejemplo, un tope de <strong>0 puntos</strong> reprueba inmediatamente la categoría y clasifica el Nivel de Riesgo de Persona en <strong>ALTO</strong>; un tope de <strong>40 puntos</strong> impide que la categoría supere 40% aunque las demás fuentes resulten favorables.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 bg-white rounded border h-100">
                            <span class="badge bg-warning-subtle text-dark mb-1">Deducción Directa de Puntos</span>
                            <div class="text-muted">
                                Resta directamente el número de puntos indicado a la calificación de la categoría cuando se detecta una señal operativa menor o documento incompleto (por ejemplo, <strong>-20 puntos</strong> por captura OCR incompleta o <strong>-10 puntos</strong> por certificado CSD revocado).
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 170px;">Categoría Evaluada</th>
                            <th style="width: 260px;">Regla de Evaluación y Fuente</th>
                            <th>Criterio de Activación e Impacto en el Expediente</th>
                            <th style="width: 190px;" class="text-center">Mecanismo de Cálculo</th>
                            <th style="width: 140px;" class="text-center">Valor (0 a 100)</th>
                            <th style="width: 85px;" class="text-center">Activa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['IDENTIDAD', 'PLD', 'CONSISTENCIA', 'REPUTACIONAL'] as $cat)
                            @php
                                $dbTriggers = $policies->get($cat)?->triggers_config ?? [];
                                $defaultTriggers = $defaultsCatalog[$cat]['triggers_config'] ?? [];
                                $catMeta = $categoryDisplayNames[$cat];
                            @endphp
                            @foreach($defaultTriggers as $tKey => $defMeta)
                                @php
                                    $savedCfg = $dbTriggers[$tKey] ?? [];
                                    $nombreRegla = $defMeta['nombre'] ?? $savedCfg['nombre'] ?? ucwords(str_replace('_', ' ', $tKey));
                                    $fuenteRegla = $defMeta['fuente'] ?? $savedCfg['fuente'] ?? 'Fuente oficial configurada';
                                    $descRegla   = $defMeta['label'] ?? $savedCfg['label'] ?? '';
                                    $efectoTipo  = strtoupper($defMeta['efecto_tipo'] ?? $savedCfg['efecto_tipo'] ?? 'TOPE');
                                    $valorActual = isset($savedCfg['valor']) ? (int) $savedCfg['valor'] : (int) ($defMeta['valor'] ?? 0);
                                    $estaActiva  = array_key_exists('activo', $savedCfg) ? !empty($savedCfg['activo']) : !empty($defMeta['activo']);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge {{ $catMeta['badge'] }} fs-12">{{ $catMeta['titulo'] }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark fs-13">{{ $nombreRegla }}</div>
                                        <div class="fs-11 text-muted mt-1">
                                            <i class="ri-database-2-line me-1"></i>Fuente: <strong>{{ $fuenteRegla }}</strong>
                                        </div>
                                    </td>
                                    <td class="fs-13 text-muted">
                                        {{ $descRegla }}
                                    </td>
                                    <td class="text-center">
                                        @if($efectoTipo === 'DESCUENTO')
                                            <span class="badge bg-warning-subtle text-dark px-2 py-1">Deducción Directa (-Pts)</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger px-2 py-1">Tope Máximo de Categoría</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="triggers[{{ $cat }}][{{ $tKey }}][valor]" class="form-control text-center fw-bold" min="0" max="100" value="{{ $valorActual }}">
                                            <span class="input-group-text">{{ $efectoTipo === 'DESCUENTO' ? 'pts' : 'máx' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" name="triggers[{{ $cat }}][{{ $tKey }}][activo]" value="1" class="form-check-input" @checked($estaActiva)>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="ri-save-line align-middle me-1"></i> Guardar Política PLD/EBR del Tenant
                </button>
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
        @php
            $eventNamesMap = [
                'HALLAZGO_CRITICO'         => 'Hallazgo Crítico Detectado',
                'NIVEL_CAMBIO'             => 'Cambio en Nivel de Riesgo',
                'RECOMENDACION_CAMBIO'     => 'Cambio en Recomendación',
                'FUENTE_CAIDA_PERSISTENTE' => 'Fuente Caída Persistente (3 fallos)',
                'REEVALUACION_VENCIDA'     => 'Reevaluación Periódica Vencida',
                'REEVALUACION_SIN_CAMBIOS' => 'Reevaluación Concluida Sin Cambios',
            ];
        @endphp
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle fs-12 mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Identificador de Evento</th>
                        <th>Tipo de Evento</th>
                        <th>Folio Origen</th>
                        <th>Transición de Nivel</th>
                        <th>Fecha y Hora</th>
                        <th class="text-center">Estado de Entrega</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentEventos ?? [] as $evt)
                        <tr>
                            <td><code>{{ $evt->evento_id }}</code></td>
                            <td>
                                <span class="badge bg-dark">{{ $eventNamesMap[$evt->tipo] ?? str_replace('_', ' ', $evt->tipo) }}</span>
                            </td>
                            <td><code>{{ $evt->folio_origen }}</code></td>
                            <td>
                                {{ $evt->payload_webhook['nivel_previo'] ?? 'Inicial' }} &rarr;
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
