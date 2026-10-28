@extends('layouts.master')
@section('title') Administración Global PLD/EBR @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Centro de Administración Global PLD/EBR y Catálogo de Fuentes (Super Admin)</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Administración PLD/EBR</li>
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

<!-- KPIs GLOBALES PLD/EBR -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-start border-4 border-primary">
            <div class="card-body">
                <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Expedientes PLD Vigentes</p>
                <h4 class="fs-22 fw-semibold mb-0">{{ $stats['total_expedientes'] ?? 0 }}</h4>
                <small class="text-muted">Sujetos con expediente único EBR</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-start border-4 border-info">
            <div class="card-body">
                <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Versiones Firmadas (JCS RFC 8785)</p>
                <h4 class="fs-22 fw-semibold mb-0">{{ $stats['total_versiones'] ?? 0 }}</h4>
                <small class="text-muted">Cadena inmutable SHA-256</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-start border-4 border-success">
            <div class="card-body">
                <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Suscripciones de Monitoreo Activas</p>
                <h4 class="fs-22 fw-semibold mb-0">{{ $stats['monitoreos_activos'] ?? 0 }}</h4>
                <small class="text-muted">Reevaluación periódica (3 / 6 meses)</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-start border-4 border-warning">
            <div class="card-body">
                <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Eventos Webhook Emitidos</p>
                <h4 class="fs-22 fw-semibold mb-0">
                    {{ $stats['eventos_emitidos'] ?? 0 }}
                    <span class="fs-13 text-muted fw-normal">({{ $stats['eventos_pendientes'] ?? 0 }} pendientes)</span>
                </h4>
                <small class="text-muted">Firmados con HMAC-SHA256 sin PII</small>
            </div>
        </div>
    </div>
</div>

<!-- RESUMEN DE OPERACIÓN PLD POR TENANT -->
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-building-2-line me-1 text-primary"></i> Resumen de Expedientes y Monitoreo PLD por Cliente (Tenant)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cliente (Sujeto Obligado)</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Total Expedientes PLD</th>
                                <th class="text-center">Nivel ALTO</th>
                                <th class="text-center">Nivel MEDIO</th>
                                <th class="text-center">Nivel BAJO</th>
                                <th class="text-center">Monitoreos Activos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenantsSummary ?? [] as $row)
                            <tr>
                                <td>#{{ $row['tenant']->id }}</td>
                                <td class="fw-semibold">{{ $row['tenant']->name }}</td>
                                <td class="text-center">
                                    @if($row['tenant']->activo)
                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold">{{ $row['total_expedientes'] }}</td>
                                <td class="text-center"><span class="badge bg-danger">{{ $row['alto'] }}</span></td>
                                <td class="text-center"><span class="badge bg-warning text-dark">{{ $row['medio'] }}</span></td>
                                <td class="text-center"><span class="badge bg-success">{{ $row['bajo'] }}</span></td>
                                <td class="text-center"><span class="badge bg-info">{{ $row['monitoreos_activos'] }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Sin clientes registrados.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ALTA DE NUEVA FUENTE AL CATÁLOGO GLOBAL -->
<div class="row">
    <div class="col-lg-12">
        <div class="card border-primary border-opacity-25">
            <div class="card-header bg-soft-primary d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-database-2-line me-1"></i> Catálogo Global de Fuentes PLD/EBR (Documento 3 §3 y §8)</h5>
            </div>
            <div class="card-body">
                <p class="mb-3 text-muted">
                    Toda fuente que alimente el Motor Determinístico de Expedientes PLD/EBR debe contar con
                    <strong>Categoría</strong> (<code>IDENTIDAD</code>, <code>PLD</code>, <code>CONSISTENCIA</code>, <code>REPUTACIONAL</code>)
                    y <strong>Criticidad</strong> (<code>ALTA</code>, <code>MEDIA</code>, <code>BAJA</code>) en este catálogo global.
                    Si un conector no está activo o registrado aquí, el motor lo excluye del cálculo y lo reporta como
                    <code>"fuente no catalogada, requiere clasificación manual"</code>.
                </p>

                <form method="POST" action="{{ route('superadmin.pld-catalogo.store') }}" class="row g-2 align-items-end mb-4 p-3 bg-light rounded border">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label fs-12 fw-semibold mb-1">Nueva Clave (<code>fuente_key</code>)</label>
                        <input type="text" name="fuente_key" class="form-control form-control-sm" placeholder="ej. buro_legal" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-12 fw-semibold mb-1">Nombre Oficial de la Fuente</label>
                        <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre descriptivo de la fuente" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fs-12 fw-semibold mb-1">Categoría</label>
                        <select name="categoria" class="form-select form-select-sm">
                            <option value="IDENTIDAD">IDENTIDAD</option>
                            <option value="PLD">PLD</option>
                            <option value="CONSISTENCIA">CONSISTENCIA</option>
                            <option value="REPUTACIONAL">REPUTACIONAL</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fs-12 fw-semibold mb-1">Criticidad</label>
                        <select name="criticidad" class="form-select form-select-sm">
                            <option value="ALTA">ALTA</option>
                            <option value="MEDIA">MEDIA</option>
                            <option value="BAJA">BAJA</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="hidden" name="activo" value="1">
                        <button type="submit" class="btn btn-sm btn-success w-100"><i class="ri-add-line align-middle me-1"></i> Agregar Fuente</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Clave Conector (<code>fuente_key</code>)</th>
                                <th>Nombre Oficial de la Fuente</th>
                                <th>Categoría</th>
                                <th>Criticidad (Falla HTTP)</th>
                                <th class="text-center">Activa</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fuentes as $f)
                            <tr>
                                <form method="POST" action="{{ route('superadmin.pld-catalogo.update', $f->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <td><code>{{ $f->fuente_key }}</code></td>
                                    <td>
                                        <input type="text" name="nombre" class="form-control form-control-sm" value="{{ $f->nombre }}" required>
                                    </td>
                                    <td>
                                        <select name="categoria" class="form-select form-select-sm">
                                            @foreach(['IDENTIDAD', 'PLD', 'CONSISTENCIA', 'REPUTACIONAL'] as $cat)
                                                <option value="{{ $cat }}" @selected($f->categoria === $cat)>{{ $cat }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="criticidad" class="form-select form-select-sm">
                                            @foreach(['ALTA', 'MEDIA', 'BAJA'] as $crit)
                                                <option value="{{ $crit }}" @selected($f->criticidad === $crit)>{{ $crit }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" name="activo" value="1" class="form-check-input" @checked($f->activo)>
                                    </td>
                                    <td>
                                        <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                                    </td>
                                </form>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
