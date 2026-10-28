@php
    $pldExp = $subject->expedientePld;
    $pldSub = $subject->pldSuscripcion;
@endphp
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-primary border-opacity-25 shadow-sm mb-0">
            <div class="card-body py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                            <i class="ri-scales-3-line"></i>
                        </span>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-bold text-dark">
                            Expediente PLD / EBR (LFPIORPI 2026)
                            @if($pldExp)
                                <span class="badge {{ $pldExp->nivel_riesgo_persona === 'ALTO' ? 'bg-danger' : ($pldExp->nivel_riesgo_persona === 'MEDIO' ? 'bg-warning text-dark' : 'bg-success') }} ms-2">
                                    Nivel de Riesgo Persona: {{ $pldExp->nivel_riesgo_persona }} ({{ $pldExp->score_global }}%)
                                </span>
                                <span class="badge bg-primary-subtle text-primary ms-1">{{ $pldExp->folio_vigente }}</span>
                            @else
                                <span class="badge bg-light text-muted ms-2">Sin versión generada</span>
                            @endif
                        </h6>
                        <div class="fs-12 text-muted">
                            @if($pldExp)
                                Recomendación: <code>{{ $pldExp->recomendacion }}</code> ·
                                EDD: <strong>{{ $pldExp->edd_recomendada ? 'SÍ' : 'NO' }}</strong> ·
                                Próxima reevaluación: <strong>{{ $pldExp->proxima_reevaluacion?->format('d/m/Y') ?? 'N/A' }}</strong>
                                @if($pldSub && $pldSub->estado === 'ACTIVA')
                                    · <span class="text-success fw-semibold"><i class="ri-radar-line"></i> Monitoreo Activo ({{ $pldSub->intervalo_meses }}m)</span>
                                @endif
                            @else
                                Genere el expediente determinístico puntuado y firmado con cadena de hashes SHA-256 (JCS RFC 8785) a partir de los resultados de los conectores.
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('tenant.subjects.pld-expediente.generate', $subject->id) }}">
                        @csrf
                        <input type="hidden" name="tipo_ejecucion" value="{{ $pldExp ? 'BAJO_DEMANDA' : 'INICIAL' }}">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="ri-shield-flash-line align-middle me-1"></i>
                            {{ $pldExp ? 'Generar Nueva Versión (v' . ($pldExp->version_vigente + 1) . ')' : 'Generar Expediente PLD (v1)' }}
                        </button>
                    </form>

                    @if($pldExp && $pldExp->folio_vigente)
                        <a href="{{ route('tenant.pld.expedientes.show', $subject->id) }}" class="btn btn-sm btn-info">
                            <i class="ri-git-commit-line align-middle me-1"></i> Ver Expediente / Versiones y Diff
                        </a>
                        <a href="{{ route('tenant.pld.expediente.pdf', $pldExp->folio_vigente) }}" class="btn btn-sm btn-soft-danger">
                            <i class="ri-file-pdf-line align-middle me-1"></i> PDF PLD
                        </a>
                        <a href="{{ route('tenant.pld.expediente.json', $pldExp->folio_vigente) }}" class="btn btn-sm btn-soft-dark">
                            <i class="ri-braces-line align-middle me-1"></i> JSON JCS
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
