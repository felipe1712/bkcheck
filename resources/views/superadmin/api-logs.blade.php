@extends('layouts.master')
@section('title') Log de APIs @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Log de Peticiones y Respuestas de APIs</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Log de APIs</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Barra de Filtros Avanzada -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="GET" action="{{ route('superadmin.api-logs') }}" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label fw-medium text-muted fs-13">Buscar Sujeto / RFC</label>
                        <div class="form-icon">
                            <input type="text" class="form-control form-control-icon" id="search" name="search" 
                                   placeholder="Nombre de empresa, persona o RFC..." value="{{ request('search') }}">
                            <i class="ri-search-2-line"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="tenant_id" class="form-label fw-medium text-muted fs-13">Cliente</label>
                        <select class="form-select" id="tenant_id" name="tenant_id">
                            <option value="">-- Todos los Clientes --</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}" {{ request('tenant_id') == $tenant->id ? 'selected' : '' }}>
                                    {{ $tenant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="source_type" class="form-label fw-medium text-muted fs-13">Servicio / API</label>
                        <select class="form-select text-uppercase" id="source_type" name="source_type">
                            <option value="">-- Todos --</option>
                            <option value="rfc" {{ request('source_type') === 'rfc' ? 'selected' : '' }}>Validación RFC</option>
                            <option value="csd" {{ request('source_type') === 'csd' ? 'selected' : '' }}>Certificados CSD</option>
                            <option value="siger" {{ request('source_type') === 'siger' ? 'selected' : '' }}>SIGER (RPC)</option>
                            <option value="sat_listas" {{ request('source_type') === 'sat_listas' ? 'selected' : '' }}>Listas SAT (69/69B)</option>
                            <option value="marcas" {{ request('source_type') === 'marcas' ? 'selected' : '' }}>Marcas IMPI</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label fw-medium text-muted fs-13">Estatus del Proceso</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">-- Todos --</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completado</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Fallido</option>
                            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Procesando</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-filter-3-line align-bottom me-1"></i> Filtrar
                        </button>
                        @if(request()->anyFilled(['search', 'tenant_id', 'source_type', 'status']))
                            <a href="{{ route('superadmin.api-logs') }}" class="btn btn-light" title="Limpiar Filtros">
                                <i class="ri-filter-off-line"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex border-bottom-dashed">
                <h5 class="card-title mb-0 flex-grow-1">Bitácora de Integración (Depuración Técnica)</h5>
                <div class="flex-shrink-0 text-muted fs-13">
                    Mostrando {{ $queries->firstItem() ?? 0 }} a {{ $queries->lastItem() ?? 0 }} de {{ $queries->total() }} consultas
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-hover table-centered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Fecha / Hora</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Sujeto (RFC)</th>
                                <th scope="col">Fuente / API</th>
                                <th scope="col">Estatus</th>
                                <th scope="col">Cód. HTTP</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($queries as $query)
                            @php
                                $logData = $query->result->raw_payload ?? null;
                                $isPendingOrProcessing = in_array($query->status, ['pending', 'processing']);
                                if ($logData) {
                                    $httpStatus = $logData['response']['status'] ?? 200;
                                } else {
                                    if ($query->status === 'failed') {
                                        $httpStatus = 500;
                                    } elseif ($isPendingOrProcessing) {
                                        $httpStatus = 'PENDIENTE';
                                    } else {
                                        $httpStatus = 200;
                                    }
                                }
                            @endphp
                            <tr>
                                <td>
                                    <span class="text-dark fw-medium">
                                        {{ $query->created_at ? $query->created_at->format('d/m/Y') : 'N/A' }}
                                    </span>
                                    <small class="text-muted d-block">
                                        {{ $query->created_at ? $query->created_at->format('H:i:s') : 'N/A' }}
                                    </small>
                                </td>
                                <td>
                                    @if($query->tenant)
                                        <span class="badge bg-light text-dark border">{{ $query->tenant->name }}</span>
                                    @else
                                        <span class="text-muted">Global</span>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        <h6 class="mb-0 fs-13">{{ $query->subject->name_or_company ?? 'N/A' }}</h6>
                                        <p class="text-muted mb-0 fs-11"><code>{{ $query->subject->rfc ?? 'N/A' }}</code></p>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary text-uppercase">{{ $query->source_type }}</span>
                                </td>
                                <td>
                                    @if($query->status === 'completed')
                                        <span class="badge bg-success">Completado</span>
                                    @elseif($query->status === 'failed')
                                        <span class="badge bg-danger">Fallido</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ $query->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($httpStatus === 'PENDIENTE')
                                        <span class="badge bg-warning-subtle text-warning">COLA / PENDIENTE</span>
                                    @elseif(is_numeric($httpStatus) && $httpStatus >= 200 && $httpStatus < 300)
                                        <span class="badge bg-success-subtle text-success">{{ $httpStatus }} OK</span>
                                    @elseif($httpStatus === 0)
                                        <span class="badge bg-secondary-subtle text-secondary">CURL ERR</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">{{ $httpStatus }} ERR</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#modal-json-{{ $query->id }}">
                                        <i class="ri-braces-line align-bottom me-1"></i> Ver JSON Payload
                                    </button>

                                    <!-- Modal for raw payload payload details -->
                                    <div class="modal fade" id="modal-json-{{ $query->id }}" tabindex="-1" aria-labelledby="modal-json-{{ $query->id }}-label" aria-hidden="true">
                                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header bg-light border-bottom">
                                                    <h5 class="modal-title" id="modal-json-{{ $query->id }}-label">
                                                        Detalles Técnicos API: {{ strtoupper($query->source_type) }} (Consulta #{{ $query->id }})
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="alert alert-info border-0 py-2 fs-12 mb-3">
                                                        <i class="ri-information-line align-middle me-1"></i>
                                                        Esta información contiene la trama cruda de entrada y salida de las consultas. Úsala para reportar incidencias con el soporte técnico.
                                                    </div>

                                                    @php
                                                        // Extract Request fields to isolate from Response
                                                        if ($logData && isset($logData['url'])) {
                                                            $requestPayload = [
                                                                'url' => $logData['url'],
                                                                'method' => $logData['method'] ?? 'POST',
                                                                'headers' => $logData['headers'] ?? [],
                                                                'body' => $logData['body'] ?? [],
                                                            ];
                                                        } else {
                                                            $requestPayload = [
                                                                'url' => 'https://nufi.azure-api.net/...',
                                                                'method' => 'POST',
                                                                'headers' => [
                                                                    'Ocp-Apim-Subscription-Key' => '***',
                                                                    'Accept' => 'application/json',
                                                                    'Content-Type' => 'application/json'
                                                                ],
                                                                'body' => [
                                                                    'rfc' => $query->subject->rfc ?? ''
                                                                ]
                                                            ];
                                                        }

                                                        // Extract Response fields
                                                        $responsePayload = null;
                                                        if ($logData && isset($logData['response'])) {
                                                            $responsePayload = $logData['response'];
                                                        } else {
                                                            if ($isPendingOrProcessing) {
                                                                $responsePayload = [
                                                                    'info' => 'Esta consulta está en la cola de tareas (' . $query->status . ') esperando a ser ejecutada.',
                                                                    'alerta' => 'Asegúrate de tener corriendo el worker de Laravel en la terminal de tu servidor.',
                                                                    'comando_requerido' => 'php artisan queue:work'
                                                                ];
                                                            } else {
                                                                $responsePayload = $query->result->processed_data ?? ($query->error_message ? ['error' => $query->error_message] : []);
                                                            }
                                                        }
                                                    @endphp

                                                    <!-- Support Technical Markdown Template (Hidden) -->
                                                    <pre id="support-payload-{{ $query->id }}" class="d-none">### REPORTE DE INCIDENCIA DE API - AVALID
===================================================
Fecha/Hora del Suceso: {{ $query->created_at ? $query->created_at->format('d/m/Y H:i:s') : 'N/A' }}
Cliente: {{ $query->tenant->name ?? 'Global' }}
Sujeto (RFC): {{ $query->subject->name_or_company ?? 'N/A' }} ({{ $query->subject->rfc ?? 'N/A' }})
Conector / API: {{ strtoupper($query->source_type) }}

---------------------------------------------------
1. DETALLES DE LA PETICIÓN (REQUEST)
---------------------------------------------------
Método: {{ $requestPayload['method'] }}
URL: {{ $requestPayload['url'] }}
Headers:
{
  "Accept": "application/json",
  "Content-Type": "application/json",
  "Ocp-Apim-Subscription-Key": "{{ $requestPayload['headers']['Ocp-Apim-Subscription-Key'] ?? '***' }}"
}

Cuerpo del Request:
{{ json_encode($requestPayload['body'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}

---------------------------------------------------
2. DETALLES DE LA RESPUESTA (RESPONSE)
---------------------------------------------------
Estatus HTTP: {{ $httpStatus }}
Cuerpo de la Respuesta / Error:
{{ json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}
===================================================</pre>

                                                    <div class="row">
                                                        <!-- Request Column -->
                                                        <div class="col-lg-6">
                                                            <div class="p-3 bg-light rounded border h-100">
                                                                <h6 class="fw-semibold text-primary mb-3">
                                                                    <i class="ri-arrow-right-up-line align-middle me-1"></i>Petición Enviada (Request)
                                                                </h6>
                                                                
                                                                <div class="mb-2 fs-12">
                                                                    <strong class="text-muted">Método:</strong> 
                                                                    <span class="badge bg-primary">{{ $requestPayload['method'] }}</span>
                                                                </div>
                                                                
                                                                <div class="mb-3 fs-12 text-break">
                                                                    <strong class="text-muted">URL:</strong> 
                                                                    <code class="text-dark">{{ $requestPayload['url'] }}</code>
                                                                </div>

                                                                <strong class="text-muted fs-12 d-block mb-1">Cuerpo (JSON Body):</strong>
                                                                <pre class="bg-dark text-white p-3 rounded fs-11 mb-0" style="max-height: 350px; overflow-y: auto;"><code class="language-json">{{ json_encode($requestPayload['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                            </div>
                                                        </div>

                                                        <!-- Response Column -->
                                                        <div class="col-lg-6 mt-3 mt-lg-0">
                                                            <div class="p-3 bg-light rounded border h-100">
                                                                <h6 class="fw-semibold text-success mb-3">
                                                                    <i class="ri-arrow-left-down-line align-middle me-1"></i>Respuesta Recibida (Response) / Error
                                                                </h6>

                                                                <div class="mb-3 fs-12">
                                                                    <strong class="text-muted">Código Estatus HTTP:</strong>
                                                                    @if($httpStatus === 'PENDIENTE')
                                                                        <span class="badge bg-warning text-dark">PENDIENTE DE COLA / PROCESANDO</span>
                                                                    @elseif(is_numeric($httpStatus) && $httpStatus >= 200 && $httpStatus < 300)
                                                                        <span class="badge bg-success">{{ $httpStatus }} OK</span>
                                                                    @elseif($httpStatus === 0)
                                                                        <span class="badge bg-secondary">CURL ERR / CONNECTION FAILED</span>
                                                                    @else
                                                                        <span class="badge bg-danger">{{ $httpStatus }} ERROR</span>
                                                                    @endif
                                                                </div>

                                                                <strong class="text-muted fs-12 d-block mb-1">Cuerpo de Respuesta (JSON Response):</strong>
                                                                <pre class="bg-dark text-white p-3 rounded fs-11 mb-0" style="max-height: 350px; overflow-y: auto;"><code class="language-json">{{ json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light border-top d-flex justify-content-between">
                                                    <button type="button" class="btn btn-outline-success" id="copy-btn-{{ $query->id }}" onclick="copySupportPayload({{ $query->id }})">
                                                        <i class="ri-file-copy-2-line align-bottom me-1"></i> Copiar para Soporte
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="ri-file-list-3-line fs-24 d-block mb-2 text-muted"></i>
                                    No se encontraron registros de logs con los filtros seleccionados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($queries->hasPages())
                    <div class="d-flex justify-content-end mt-4">
                        {{ $queries->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    function copySupportPayload(queryId) {
        const textElement = document.getElementById('support-payload-' + queryId);
        if (!textElement) return;

        navigator.clipboard.writeText(textElement.textContent).then(() => {
            const btn = document.getElementById('copy-btn-' + queryId);
            const originalHtml = btn.innerHTML;
            
            // Success state transition
            btn.innerHTML = '<i class="ri-checkbox-circle-line align-bottom me-1"></i> ¡Copiado a Portapapeles!';
            btn.className = 'btn btn-success';
            
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.className = 'btn btn-outline-success';
            }, 2500);
        }).catch(err => {
            console.error('Error al copiar el texto: ', err);
            alert('No se pudo copiar de forma automática. Por favor, selecciona y copia manualmente el contenido.');
        });
    }
</script>
@endsection
