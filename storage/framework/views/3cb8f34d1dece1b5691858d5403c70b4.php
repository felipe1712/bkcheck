<?php $__env->startSection('title'); ?> Consultas y Consumo <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Consumo de APIs y Facturación</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('tenant.dashboard')); ?>">Inicio</a></li>
                    <li class="breadcrumb-item active">Consumo</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Period Filter -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <form action="<?php echo e(route('tenant.consumption')); ?>" method="GET" class="row align-items-center g-3">
                    <div class="col-auto">
                        <label for="period" class="col-form-label fw-semibold">Seleccionar Período de Facturación:</label>
                    </div>
                    <div class="col-auto">
                        <select class="form-select" id="period" name="period" onchange="this.form.submit()">
                            <?php $__currentLoopData = $availablePeriods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $per): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $year = substr($per, 0, 4);
                                    $monthNum = substr($per, 5, 2);
                                    $months = [
                                        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                                        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                                        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
                                    ];
                                    $monthName = $months[$monthNum] ?? $monthNum;
                                ?>
                                <option value="<?php echo e($per); ?>" <?php echo e($selectedPeriod === $per ? 'selected' : ''); ?>>
                                    <?php echo e($monthName); ?> <?php echo e($year); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line align-bottom me-1"></i> Filtrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Stats Summary -->
<?php
    $limit = $tenant->limite_consultas_mensual;
    $percentUsed = $limit > 0 ? min(100, round(($totalQueries / $limit) * 100, 1)) : 0;
    $barColorClass = $percentUsed > 90 ? 'bg-danger' : ($percentUsed > 70 ? 'bg-warning' : 'bg-success');
    $selectedYear = substr($selectedPeriod, 0, 4);
?>

<div class="row">
    <!-- Monthly Usage Count -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-0">Consultas del Mes</p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-1"><?php echo e($totalQueries); ?> <span class="fs-13 text-muted">/ <?php echo e($limit); ?></span></h4>
                        <span class="badge bg-primary-subtle text-primary">Límite mensual</span>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-3 shadow">
                            <i class="ri-user-search-line text-primary"></i>
                        </span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress progress-sm">
                        <div class="progress-bar <?php echo e($barColorClass); ?>" role="progressbar" style="width: <?php echo e($percentUsed); ?>%" aria-valuenow="<?php echo e($percentUsed); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <span class="text-muted fs-12 d-block mt-1"><?php echo e($percentUsed); ?>% de la cuota consumida</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Spending -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-0">Gasto Mensual Estimado</p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-1">$<?php echo e(number_format($totalCost, 2)); ?> <span class="fs-13 text-muted">USD</span></h4>
                        <span class="badge bg-success-subtle text-success">Período seleccionado</span>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-3 shadow">
                            <i class="ri-money-dollar-circle-line text-success"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Yearly Usage Count -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-0">Consultas del Año (<?php echo e($selectedYear); ?>)</p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-1"><?php echo e($yearlyQueries); ?> <span class="fs-13 text-muted">consultas</span></h4>
                        <span class="badge bg-info-subtle text-info">Acumulado anual</span>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded fs-3 shadow">
                            <i class="ri-bar-chart-box-line text-info"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Yearly Spending -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-0">Gasto Anual Estimado (<?php echo e($selectedYear); ?>)</p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-1">$<?php echo e(number_format($yearlyCost, 2)); ?> <span class="fs-13 text-muted">USD</span></h4>
                        <span class="badge bg-warning-subtle text-warning">Acumulado anual</span>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-3 shadow">
                            <i class="ri-funds-box-line text-warning"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Consumption Breakdown by Service -->
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-0">
                <h5 class="card-title mb-0">Desglose de Consumo por Servicio</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Servicio / API</th>
                                <th scope="col">Consultas Realizadas</th>
                                <th scope="col">Tarifa Unitario</th>
                                <th scope="col">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $servicesList = [
                                    'rfc' => ['name' => 'Validación de RFC (SAT)', 'price' => 1.50],
                                    'csd' => ['name' => 'Certificados CSD y FIEL (SAT)', 'price' => 3.00],
                                    'siger' => ['name' => 'Registro Público de Comercio (SIGER)', 'price' => 8.00],
                                    'sat_listas' => ['name' => 'Listas SAT 69/69B (EFOS/EDOS)', 'price' => 1.00],
                                    'marcas' => ['name' => 'Búsqueda de Marcas (IMPI)', 'price' => 5.00],
                                ];
                            ?>
                            
                            <?php $__currentLoopData = $servicesList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $serv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $usageRecord = $usages->firstWhere('servicio', $key);
                                    $count = $usageRecord ? $usageRecord->conteo : 0;
                                    $subtotal = $count * $serv['price'];
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($serv['name']); ?></td>
                                    <td><?php echo e($count); ?></td>
                                    <td>$<?php echo e(number_format($serv['price'], 2)); ?> USD</td>
                                    <td class="fw-semibold text-dark">$<?php echo e(number_format($subtotal, 2)); ?> USD</td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            
                            <!-- Total row -->
                            <tr class="table-light border-top-double">
                                <td colspan="2" class="fw-bold fs-14">Total Consolidado</td>
                                <td></td>
                                <td class="fw-bold fs-14 text-primary">$<?php echo e(number_format($totalCost, 2)); ?> USD</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Query Logs -->
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-0">
                <h5 class="card-title mb-0">Detalle de Investigaciones en el Período</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-nowrap table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Fecha y Hora</th>
                                <th scope="col">Usuario Ejecutor</th>
                                <th scope="col">Sujeto</th>
                                <th scope="col">RFC</th>
                                <th scope="col">Fuente Consultada</th>
                                <th scope="col">Dirección IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $auditLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($log->created_at->format('d/m/Y H:i:s')); ?></td>
                                <td class="fw-semibold"><?php echo e($log->user ? $log->user->name : 'N/A'); ?></td>
                                <td><?php echo e($log->subject_name); ?></td>
                                <td><code><?php echo e($log->subject_rfc); ?></code></td>
                                <td><span class="badge bg-primary-subtle text-primary"><?php echo e($log->fuente); ?></span></td>
                                <td><?php echo e($log->ip_address); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No se registraron consultas de background check en este período.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <?php echo e($auditLogs->appends(['period' => $selectedPeriod])->links()); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Atlas\resources\views/tenant/consumption.blade.php ENDPATH**/ ?>