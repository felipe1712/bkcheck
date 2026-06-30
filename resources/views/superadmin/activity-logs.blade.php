@extends('layouts.master')
@section('title') Bitácora de Actividad @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Bitácora de Actividad General</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Bitácora</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h5 class="card-title mb-0 flex-grow-1">Registro de Operaciones de Usuarios</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-hover table-centered align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Fecha / Hora</th>
                                <th scope="col">Usuario (Causante)</th>
                                <th scope="col">Acción</th>
                                <th scope="col">Elemento Afectado</th>
                                <th scope="col">ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs flex-shrink-0 me-2">
                                            <span class="avatar-title bg-info-subtle rounded-circle text-info fs-12">
                                                {{ substr($log->causer->name ?? 'S', 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fs-13">{{ $log->causer->name ?? 'Sistema' }}</h6>
                                            <p class="text-muted mb-0 fs-11">{{ $log->causer->email ?? 'Automático' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-wrap">{{ $log->description }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ class_basename($log->subject_type) ?: 'N/A' }}</span>
                                </td>
                                <td>#{{ $log->subject_id ?: '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay registros de actividad.</td>
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
