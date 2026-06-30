<?php $__env->startSection('title'); ?> Inicio <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Panel de Control: <?php echo e($tenant->name); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">SaaS</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Active Projects -->
    <div class="col-xl-4 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Proyectos Activos</p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4"><?php echo e($projectsCount); ?></h4>
                        <a href="<?php echo e(route('tenant.projects.index')); ?>" class="text-decoration-underline text-primary">Ver todos</a>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-3 shadow material-shadow">
                            <i class="ri-folder-open-line text-primary"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subjects Queried -->
    <div class="col-xl-4 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Sujetos Investigados</p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4"><?php echo e($subjectsCount); ?></h4>
                        <a href="<?php echo e(route('tenant.subjects.index')); ?>" class="text-decoration-underline text-info">Ver todos</a>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded fs-3 shadow material-shadow">
                            <i class="ri-user-search-line text-info"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly API Consumption quota -->
    <div class="col-xl-4 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Consultas Usadas / Límite</p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div class="w-100 me-3">
                        <?php
                            $limit = $tenant->limite_consultas_mensual;
                            $percentage = $limit > 0 ? min(round(($monthlyUsage / $limit) * 100), 100) : 100;
                            $isLimitReached = $monthlyUsage >= $limit;
                        ?>
                        <h4 class="fs-20 fw-semibold ff-secondary mb-2"><?php echo e($monthlyUsage); ?> <span class="fs-13 text-muted">de <?php echo e($limit); ?></span></h4>
                        <div class="progress progress-sm">
                            <div class="progress-bar <?php echo e($isLimitReached ? 'bg-danger' : ($percentage > 80 ? 'bg-warning' : 'bg-success')); ?>" 
                                 role="progressbar" style="width: <?php echo e($percentage); ?>%" aria-valuenow="<?php echo e($percentage); ?>" 
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted mt-1 d-block"><?php echo e($percentage); ?>% de la cuota mensual consumido</small>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title <?php echo e($isLimitReached ? 'bg-danger-subtle' : 'bg-success-subtle'); ?> rounded fs-3 shadow material-shadow">
                            <i class="ri-search-eye-line <?php echo e($isLimitReached ? 'text-danger' : 'text-success'); ?>"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if($monthlyUsage >= $tenant->limite_consultas_mensual): ?>
<div class="alert alert-danger" role="alert">
    <strong>¡Alerta de Límite!</strong> Tu empresa ha alcanzado el límite mensual asignado de consultas de API (<?php echo e($tenant->limite_consultas_mensual); ?>). Las búsquedas nuevas quedarán deshabilitadas hasta el inicio del siguiente periodo.
</div>
<?php endif; ?>

<div class="row mt-4">
    <!-- Recent Investigations -->
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h5 class="card-title mb-0 flex-grow-1">Sujetos Investigados Recientemente</h5>
                <a href="<?php echo e(route('tenant.subjects.create')); ?>" class="btn btn-primary btn-sm"><i class="ri-user-add-line align-bottom me-1"></i> Nueva Consulta</a>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-hover table-centered align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Nombre / Razón Social</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">RFC</th>
                                <th scope="col">Proyecto</th>
                                <th scope="col">Fecha de Alta</th>
                                <th scope="col">Consentimiento</th>
                                <th scope="col">Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $recentSubjects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subj): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($subj->name_or_company); ?></td>
                                <td>
                                    <span class="badge <?php echo e($subj->tipo == 'persona_fisica' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary'); ?>">
                                        <?php echo e($subj->tipo == 'persona_fisica' ? 'Física' : 'Moral'); ?>

                                    </span>
                                </td>
                                <td><code><?php echo e($subj->rfc); ?></code></td>
                                <td>
                                    <a href="<?php echo e(route('tenant.projects.show', $subj->project->id)); ?>" class="text-primary"><?php echo e($subj->project->name); ?></a>
                                </td>
                                <td><?php echo e($subj->created_at->format('d/m/Y H:i')); ?></td>
                                <td>
                                    <span class="badge bg-success"><i class="ri-check-line align-middle"></i> Otorgado</span>
                                </td>
                                <td>
                                    <a href="<?php echo e(route('tenant.subjects.show', $subj->id)); ?>" class="btn btn-sm btn-soft-primary">
                                        <i class="ri-eye-line"></i> Abrir Expediente
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No se registran sujetos investigados recientemente.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Atlas\resources\views/tenant/dashboard.blade.php ENDPATH**/ ?>