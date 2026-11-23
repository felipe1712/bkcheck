@if(!empty($radiografia))
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary-subtle border-bottom border-primary-subtle d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i class="ri-crosshair-2-line fs-20 text-primary me-2"></i>
            <div>
                <h5 class="card-title mb-0 text-primary fw-bold">Radiografía de Localización e Inteligencia (Porcupine v3)</h5>
                <small class="text-muted">Consolidación de identidad, domicilios conocidos, actividad laboral, riesgos penales y huella OSINT</small>
            </div>
        </div>
        @if(!is_null($radiografia['identidad']['confianza_score']))
            @php
                $conf = $radiografia['identidad']['confianza_score'];
                $confColor = $conf >= 85 ? 'success' : ($conf >= 60 ? 'warning' : 'danger');
            @endphp
            <span class="badge bg-{{ $confColor }} fs-12 px-3 py-2">
                <i class="ri-shield-check-line me-1"></i> Confianza Identidad: {{ $conf }}%
            </span>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Columna 1: Identidad & Salvaguarda CURP->RFC --}}
            <div class="col-lg-4">
                <div class="p-3 bg-light rounded h-100 border">
                    <h6 class="fw-bold text-uppercase fs-12 text-muted mb-2">
                        <i class="ri-fingerprint-line me-1 text-primary"></i> Identidad Validada
                    </h6>
                    <p class="mb-1 fs-13"><strong>Nombre:</strong> {{ $radiografia['identidad']['nombre_completo'] ?? 'Pendiente' }}</p>
                    <p class="mb-1 fs-13">
                        <strong>CURP:</strong> {{ $radiografia['identidad']['curp'] ?? 'N/A' }}
                        @if($radiografia['identidad']['curp_valida'] === true)
                            <span class="badge bg-success-subtle text-success fs-10">RENAPO OK</span>
                        @endif
                    </p>
                    <p class="mb-1 fs-13">
                        <strong>RFC:</strong> {{ $radiografia['identidad']['rfc'] ?? 'Por calcular' }}
                        @if(($radiografia['identidad']['rfc_source'] ?? '') === 'calculated_from_curp')
                            <span class="badge bg-info-subtle text-info fs-10">Calculado desde CURP</span>
                        @endif
                        @if($radiografia['identidad']['rfc_valido'] === true)
                            <span class="badge bg-success-subtle text-success fs-10">SAT OK</span>
                        @elseif($radiografia['identidad']['rfc_valido'] === false)
                            <span class="badge bg-danger-subtle text-danger fs-10">No validado</span>
                        @endif
                    </p>
                    @if(!empty($radiografia['telefonos']))
                        <p class="mb-1 fs-13"><strong>Teléfonos:</strong> {{ implode(', ', $radiografia['telefonos']) }}</p>
                    @endif
                    @if(!empty($subject->email))
                        <p class="mb-0 fs-13"><strong>Correo:</strong> {{ $subject->email }}</p>
                    @endif
                </div>
            </div>

            {{-- Columna 2: Domicilios Conocidos --}}
            <div class="col-lg-4">
                <div class="p-3 bg-light rounded h-100 border">
                    <h6 class="fw-bold text-uppercase fs-12 text-muted mb-2">
                        <i class="ri-map-pin-user-line me-1 text-danger"></i> Domicilios Conocidos ({{ count($radiografia['domicilios']) }})
                    </h6>
                    @forelse($radiografia['domicilios'] as $dom)
                        <div class="mb-2 pb-2 border-bottom border-dashed">
                            <div class="fs-13 fw-medium">{{ $dom['direccion'] }}</div>
                            <div class="fs-11 text-muted">
                                <span class="badge bg-secondary-subtle text-secondary">{{ $dom['fuente'] }}</span>
                                @if(!empty($dom['fecha_reporte']))
                                    · {{ $dom['fecha_reporte'] }}
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted fs-13 mb-0">Sin domicilios reportados aún en fuentes consultadas.</p>
                    @endforelse
                </div>
            </div>

            {{-- Columna 3: Actividad Laboral, Penal y OSINT --}}
            <div class="col-lg-4">
                <div class="p-3 bg-light rounded h-100 border">
                    <h6 class="fw-bold text-uppercase fs-12 text-muted mb-2">
                        <i class="ri-briefcase-4-line me-1 text-success"></i> Actividad Laboral y Riesgos
                    </h6>
                    @if(!empty($radiografia['actividad_laboral']['patron_actual']))
                        @php $pat = $radiografia['actividad_laboral']['patron_actual']; @endphp
                        <p class="mb-1 fs-13">
                            <strong>Patrón Actual (IMSS):</strong> {{ $pat['patron'] ?? 'N/A' }}
                            <span class="badge bg-success-subtle text-success fs-10">{{ $pat['entidad_federativa'] ?? '' }}</span>
                        </p>
                    @else
                        <p class="mb-1 fs-13 text-muted">Sin patrón vigente en IMSS.</p>
                    @endif

                    @if(!empty($radiografia['actividad_laboral']['cedulas_profesionales']))
                        @php $ced = $radiografia['actividad_laboral']['cedulas_profesionales'][0]; @endphp
                        <p class="mb-1 fs-13">
                            <strong>Cédula SEP:</strong> {{ $ced['num_cedula'] ?? '' }} — {{ $ced['profesion'] ?? '' }}
                            <span class="text-muted fs-11">({{ $ced['institucion'] ?? '' }})</span>
                        </p>
                    @endif

                    @php $penal = $radiografia['riesgo_legal']['antecedentes_penales'] ?? []; @endphp
                    <p class="mb-1 fs-13">
                        <strong>Antecedentes Penales:</strong>
                        @if(!empty($penal))
                            @if(!empty($penal['tiene_antecedentes']))
                                <span class="badge bg-danger">{{ $penal['total_registros'] ?? 1 }} registro(s)</span>
                            @else
                                <span class="badge bg-success-subtle text-success">Sin registros</span>
                            @endif
                        @else
                            <span class="text-muted">Pendiente</span>
                        @endif
                    </p>

                    <p class="mb-1 fs-13">
                        <strong>Sherlock / OSINT:</strong>
                        <span class="badge bg-info-subtle text-info">{{ $radiografia['huella_digital']['total_redes'] }} perfiles</span>
                    </p>

                    @if(!empty($radiografia['huella_digital']['tlaloc_phone']['encontrado']))
                        @php $tp = $radiografia['huella_digital']['tlaloc_phone']; @endphp
                        <p class="mb-1 fs-12 text-muted">
                            <strong>Tlaloc PNN:</strong> {{ $tp['carrier'] ?? 'N/A' }} ({{ $tp['tipo_red'] ?? '' }} · {{ $tp['municipio'] ?? '' }}, {{ $tp['estado'] ?? '' }})
                        </p>
                    @endif

                    @if(!empty($radiografia['huella_digital']['horus']['phone_intel']))
                        @php $ph = $radiografia['huella_digital']['horus']['phone_intel']; @endphp
                        <p class="mb-1 fs-12 text-muted">
                            <strong>Horus Tel:</strong> {{ $ph['carrier'] ?? 'N/A' }} ({{ $ph['region'] ?? 'N/A' }})
                        </p>
                    @endif

                    @if(!empty($radiografia['huella_digital']['robin']['resumen']))
                        <p class="mb-0 fs-12 text-muted">
                            <strong>Robin DarkWeb:</strong> {{ \Illuminate\Support\Str::limit($radiografia['huella_digital']['robin']['resumen'], 120) }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Mapa Interactivo de Localización (Leaflet + perliedman/leaflet-control-geocoder) --}}
        <div class="mt-3 pt-3 border-top">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold text-uppercase fs-12 text-muted mb-0">
                    <i class="ri-map-2-line me-1 text-primary"></i> Mapa de Domicilios Conocidos y Búsqueda Geográfica (OpenStreetMap / Nominatim)
                </h6>
                <span class="badge bg-light text-muted border fs-10">Costo \$0 · Leaflet Control Geocoder</span>
            </div>
            <div id="porcupine-location-map" style="height: 300px; width: 100%; border-radius: 6px; border: 1px solid #dee2e6; z-index: 1;"></div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var mapEl = document.getElementById('porcupine-location-map');
    if (!mapEl || typeof L === 'undefined') return;

    var map = L.map('porcupine-location-map').setView([23.6345, -102.5528], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var geocoder = L.Control.Geocoder.nominatim({
        geocodingQueryParams: { countrycodes: 'mx' }
    });

    L.Control.geocoder({
        geocoder: geocoder,
        defaultMarkGeocode: true,
        placeholder: 'Buscar dirección en México...'
    }).addTo(map);

    var domicilios = @json($radiografia['domicilios'] ?? []);
    var bounds = [];

    domicilios.forEach(function (dom, idx) {
        if (!dom.direccion) return;
        setTimeout(function () {
            geocoder.geocode(dom.direccion, function (results) {
                if (results && results.length > 0) {
                    var center = results[0].center;
                    var marker = L.marker([center.lat, center.lng]).addTo(map);
                    marker.bindPopup('<strong>' + (dom.fuente || 'Fuente') + '</strong><br>' + dom.direccion);
                    bounds.push([center.lat, center.lng]);
                    if (bounds.length === 1) {
                        map.setView(bounds[0], 14);
                    } else if (bounds.length > 1) {
                        map.fitBounds(bounds, { padding: [30, 30] });
                    }
                }
            });
        }, idx * 450);
    });
});
</script>
@endif
