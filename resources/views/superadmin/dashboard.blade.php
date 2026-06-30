@extends('layouts.master')
@section('title') Dashboard Global @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Dashboard Global de Control (Super Admin)</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">Super Admin</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Total Tenants -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Clientes Totales</p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $tenantsCount }}</h4>
                        <span class="badge bg-success-subtle text-success">{{ $activeTenantsCount }} Activos</span>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-3 shadow material-shadow">
                            <i class="ri-git-repository-private-line text-primary"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Users -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Usuarios de Clientes</p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $usersCount }}</h4>
                        <span class="text-muted">En todos los clientes</span>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded fs-3 shadow material-shadow">
                            <i class="ri-group-line text-info"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Queries -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Consultas de API (Mes)</p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $monthlyStats->total_queries ?? 0 }}</h4>
                        <span class="text-muted">Periodo actual</span>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-3 shadow material-shadow">
                            <i class="ri-search-eye-line text-warning"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Finanzas Estimadas -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Margen Estimado (Total)</p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        @php
                            $cost = $usageStats->total_cost ?? 0;
                            $revenue = $usageStats->total_revenue ?? 0;
                            $margin = $revenue - $cost;
                        @endphp
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">${{ number_format($margin, 2) }}</h4>
                        <span class="text-success">${{ number_format($revenue, 2) }} Ingreso</span> / 
                        <span class="text-danger">${{ number_format($cost, 2) }} Costo</span>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-3 shadow material-shadow">
                            <i class="ri-money-dollar-circle-line text-success"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Queries by Service -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Consultas por Tipo de Servicio</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-hover table-centered align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Servicio</th>
                                <th scope="col" class="text-center">Consultas Totales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($serviceStats as $stat)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs flex-shrink-0 me-2">
                                            <span class="avatar-title bg-light rounded text-primary fs-14">
                                                <i class="ri-settings-4-line"></i>
                                            </span>
                                        </div>
                                        <span class="fw-medium text-capitalize">{{ str_replace('_', ' ', $stat->servicio) }}</span>
                                    </div>
                                </td>
                                <td class="text-center fw-semibold">{{ $stat->total_queries }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No se registran consumos todavía.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Actividad Reciente del Sistema</h4>
                <a href="{{ route('superadmin.activity-logs') }}" class="btn btn-soft-info btn-sm">Ver Bitácora Completa</a>
            </div>
            <div class="card-body">
                <div class="live-preview">
                    <div class="profile-timeline">
                        <div class="accordion accordion-flush" id="todayTimeline">
                            @forelse($recentActivity as $act)
                            <div class="accordion-item border-0">
                                <div class="accordion-header" id="heading{{ $act->id }}">
                                    <div class="accordion-button p-0 shadow-none d-flex align-items-start">
                                        <div class="avatar-xs flex-shrink-0">
                                            <div class="avatar-title rounded-circle bg-light text-primary">
                                                <i class="ri-user-line"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="fs-14 mb-1 text-muted">{{ $act->created_at->diffForHumans() }}</h6>
                                            <p class="text-body mb-0">{{ $act->description }}</p>
                                            <small class="text-muted">Por: {{ $act->causer->name ?? 'Sistema/Anónimo' }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <p class="text-center text-muted">No hay registros de actividad recientes.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
