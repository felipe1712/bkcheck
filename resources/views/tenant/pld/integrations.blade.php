@extends('layouts.master')
@section('title') API & Webhooks PLD/EBR @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">API &amp; Webhooks de Monitoreo PLD/EBR</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item">Configuración</li>
                    <li class="breadcrumb-item active">API &amp; Webhook</li>
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

<!-- BLOQUE 1: CONECTORES -->
<div class="card border-primary border-opacity-25 shadow-sm mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="card-title text-white mb-0">
            <i class="ri-links-line me-1 align-middle"></i>
            Conectores
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="p-3 bg-light rounded border h-100">
                    <h6 class="fw-bold text-primary mb-2">
                        <i class="ri-key-2-line me-1 align-middle"></i> 1. Integración vía API REST (Pull / Bajo Demanda)
                    </h6>
                    <p class="fs-13 text-muted mb-0">
                        Con su <strong>API Key de Organización</strong> puede conectar directamente su ERP, CRM, core financiero o portal de onboarding para:
                        <br>• Dar de alta o baja el monitoreo periódico de clientes o candidatos.
                        <br>• Descargar automáticamente cada versión del expediente en formato <strong>JSON canónico (JCS RFC 8785)</strong> o <strong>PDF</strong> para integrarlo a su expediente único.
                        <br>• Verificar criptográficamente que ningún expediente haya sido alterado mediante su cadena de hashes <strong>SHA-256</strong>.
                    </p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="p-3 bg-light rounded border h-100">
                    <h6 class="fw-bold text-primary mb-2">
                        <i class="ri-webhook-line me-1 align-middle"></i> 2. Alertas en Tiempo Real vía Webhooks (Push)
                    </h6>
                    <p class="fs-13 text-muted mb-0">
                        Sin necesidad de consultar manualmente, AvalID notificará a su servidor en cuanto una reconsulta periódica (cada 3 o 6 meses) detecte:
                        <br>• Aparición de un <strong>Hallazgo Crítico</strong> nuevo (PEP, lista 69-B del SAT o listas internacionales OFAC/ONU).
                        <br>• Un <strong>Cambio en el Nivel de Riesgo de Persona</strong> (<code>BAJO</code>, <code>MEDIO</code>, <code>ALTO</code>) o en la recomendación de acción.
                        <br>• Una fuente gubernamental caída de forma persistente o una reevaluación vencida.
                    </p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="p-3 bg-light rounded border h-100">
                    <h6 class="fw-bold text-primary mb-2">
                        <i class="ri-shield-keyhole-line me-1 align-middle"></i> 3. Seguridad Criptográfica y Cero Exposición de PII
                    </h6>
                    <p class="fs-13 text-muted mb-0">
                        Por diseño de privacidad y reserva legal (<strong>LFPIORPI Art. 63 fr. II</strong> y <strong>LFPDPPP</strong>):
                        <br>• Los mensajes de Webhook <strong>jamás transmiten datos personales</strong> (sin nombre, RFC, CURP ni domicilio) ni el texto del hallazgo; envían únicamente el <code>folio</code> y una referencia opaca para que su sistema descargue el detalle autenticado.
                        <br>• Cada mensaje va firmado con <strong>HMAC-SHA256</strong> y estampa de tiempo (ventana anti-repetición de 5 minutos).
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BLOQUE 2: CONFIGURACIÓN DE CREDENCIALES API Y WEBHOOK + REFERENCIA DE ENDPOINTS -->
<div class="row">
    <div class="col-lg-6">
        <div class="card border-primary h-100">
            <div class="card-header bg-soft-primary d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-settings-4-line me-1 text-primary"></i> Credenciales API y Configuración de Webhooks HMAC</h5>
                <span class="badge bg-primary">Activo en su Cuenta</span>
            </div>
            <div class="card-body">
                <label class="form-label fw-bold">API Key de su Organización (Header <code>X-Tenant-Api-Key</code> o <code>Bearer</code>):</label>
                <div class="input-group mb-2">
                    <input type="text" class="form-control font-monospace" id="tenantApiKeyInput" value="{{ $tenant->api_key }}" readonly>
                    <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('tenantApiKeyInput').value)">
                        <i class="ri-file-copy-line"></i> Copiar
                    </button>
                    <form method="POST" action="{{ route('tenant.pld.policy.rotate-key') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Regenerar la API Key? Las integraciones que usen la clave anterior deberán actualizarse.')">
                            Rotar Key
                        </button>
                    </form>
                </div>
                <small class="text-muted d-block mb-3">
                    Incluya este token en el encabezado HTTP <code>X-Tenant-Api-Key</code> de todas las peticiones desde su servidor hacia AvalID.
                </small>

                <hr>
                <h6 class="fw-bold mb-2"><i class="ri-webhook-line me-1 text-primary"></i> Registrar / Actualizar Endpoint Receptor de Webhooks</h6>
                <form method="POST" action="{{ route('tenant.pld.policy.webhook') }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-7">
                            <label class="form-label fs-12 fw-semibold mb-1">URL HTTPS receptora en su servidor</label>
                            <input type="url" name="url" class="form-control form-control-sm" value="{{ $webhooks->first()?->url }}" placeholder="https://su-sistema.com/webhooks/avalid" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fs-12 fw-semibold mb-1">Secreto de Firma HMAC-SHA256 (mín. 16 caracteres)</label>
                            <input type="password" name="secreto" class="form-control form-control-sm" placeholder="whsec_****************" minlength="16" required>
                        </div>
                        <div class="col-12 mt-2">
                            <label class="form-label fs-12 fw-semibold mb-1 d-block">Eventos a Notificar Automáticamente:</label>
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
                        <div class="col-12 d-flex justify-content-between align-items-center mt-3">
                            <label class="form-check-label fs-12">
                                <input type="checkbox" name="incluir_sin_cambios" value="1" class="form-check-input me-1" @checked($webhooks->first()?->incluir_sin_cambios)>
                                Notificar también cuando una reevaluación periódica concluya sin cambios
                            </label>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="ri-save-line align-middle me-1"></i> Guardar Webhook
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-code-box-line me-1 text-dark"></i> Catálogo de Endpoints REST y Firma HMAC</h5>
                <span class="badge bg-dark">Referencia Técnica</span>
            </div>
            <div class="card-body">
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered fs-12 mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Método y Ruta</th>
                                <th>Operación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>POST /api/personas/{id}/monitoreo</code></td>
                                <td>Activa la suscripción de reconsulta periódica (finalidad, consentimiento e intervalo).</td>
                            </tr>
                            <tr>
                                <td><code>DELETE /api/personas/{id}/monitoreo</code></td>
                                <td>Da de baja el monitoreo por término de relación o retiro de consentimiento.</td>
                            </tr>
                            <tr>
                                <td><code>POST /api/personas/{id}/expedientes</code></td>
                                <td>Genera una nueva versión inmutable del expediente bajo demanda.</td>
                            </tr>
                            <tr>
                                <td><code>GET /api/personas/{id}/expedientes</code></td>
                                <td>Lista el historial de versiones (folio, fecha, nivel y cadena de hashes).</td>
                            </tr>
                            <tr>
                                <td><code>GET /api/expedientes/{folio}</code></td>
                                <td>Descarga el expediente íntegro en JSON o en PDF (<code>?format=pdf</code>).</td>
                            </tr>
                            <tr>
                                <td><code>GET /api/expedientes/{folio}/diff</code></td>
                                <td>Obtiene el comparativo estructurado de cambios entre dos versiones.</td>
                            </tr>
                            <tr>
                                <td><code>POST /api/expedientes/verificar</code></td>
                                <td>Verifica un archivo JSON contra su firma canónica SHA-256 (JCS RFC 8785).</td>
                            </tr>
                            <tr>
                                <td><code>GET /api/eventos?desde=</code></td>
                                <td>Recupera eventos pendientes o emitidos desde una fecha ISO 8601.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-2 bg-dark text-light rounded font-monospace fs-11">
                    <div class="text-info fw-bold mb-1">// Encabezados enviados por AvalID en cada Webhook:</div>
                    <div>X-AvalID-Event-Id: evt_01j... <span class="text-white-50">(Clave de idempotencia)</span></div>
                    <div>X-AvalID-Timestamp: 1793220000 <span class="text-white-50">(Rechazar si difiere &gt; 5 min)</span></div>
                    <div>X-AvalID-Signature: sha256=HMAC_SHA256(timestamp + "." + rawBody, secreto)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BLOQUE 3: BITÁCORA RECIENTE DE EVENTOS WEBHOOK EMITIDOS -->
<div class="card mt-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ri-notification-badge-line me-1 text-primary"></i> Bitácora de Eventos PLD / Webhooks Emitidos (Sin Datos Personales)</h5>
        <span class="badge bg-secondary">Últimos 25 eventos</span>
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
