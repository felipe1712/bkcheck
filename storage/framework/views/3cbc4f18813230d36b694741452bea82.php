<?php $__env->startSection('title'); ?> Auditoría de Consultas <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Registro Inmutable de Auditoría</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('superadmin.dashboard')); ?>">Super Admin</a></li>
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
                            <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($log->created_at->format('d/m/Y H:i:s')); ?></td>
                                <td>
                                    <span class="fw-semibold text-primary"><?php echo e($log->tenant->name ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <div>
                                        <h6 class="mb-0 fs-13"><?php echo e($log->user->name ?? 'N/A'); ?></h6>
                                        <p class="text-muted mb-0 fs-11"><?php echo e($log->user->email ?? ''); ?></p>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-medium"><?php echo e($log->subject_name); ?></span>
                                </td>
                                <td>
                                    <code><?php echo e($log->subject_rfc ?: '-'); ?></code>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary text-uppercase"><?php echo e(str_replace('_', ' ', $log->fuente)); ?></span>
                                </td>
                                <td><?php echo e($log->ip_address ?: '-'); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay consultas de auditoría registradas.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <?php echo e($logs->links()); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Atlas\resources\views/superadmin/audit-logs.blade.php ENDPATH**/ ?>