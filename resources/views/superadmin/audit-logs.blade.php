@extends('layouts.master')
@section('title') Auditoría de Consultas @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Registro Inmutable de Auditoría</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Auditoría</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h5 class="card-title mb-0 flex-grow-1">Bitácora de Consultas a APIs de Terceros</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-hover table-centered align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Fecha / Hora</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Usuario (Consultor)</th>
                                <th scope="col">Sujeto Investigado</th>
                                <th scope="col">RFC Sujeto</th>
                                <th scope="col">Fuente / API</th>
                                <th scope="col">Dirección IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    <span class="fw-semibold text-primary">{{ $log->tenant->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <div>
                                        <h6 class="mb-0 fs-13">{{ $log->user->name ?? 'N/A' }}</h6>
                                        <p class="text-muted mb-0 fs-11">{{ $log->user->email ?? '' }}</p>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $log->subject_name }}</span>
                                </td>
                                <td>
                                    <code>{{ $log->subject_rfc ?: '-' }}</code>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary text-uppercase">{{ str_replace('_', ' ', $log->fuente) }}</span>
                                </td>
                                <td>{{ $log->ip_address ?: '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay consultas de auditoría registradas.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
