<?php $__env->startSection('title'); ?> Sujetos de Investigación <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Listado General de Sujetos</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('tenant.dashboard')); ?>">Inicio</a></li>
                    <li class="breadcrumb-item active">Sujetos</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php if(session('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo e(session('success')); ?>

    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-0 align-items-center d-flex">
                <h5 class="card-title mb-0 flex-grow-1">Historial General de Sujetos Consultados</h5>
                <div class="flex-shrink-0">
                    <a href="<?php echo e(route('tenant.subjects.create')); ?>" class="btn btn-primary btn-sm"><i class="ri-user-add-line align-bottom me-1"></i> Registrar Sujeto</a>
                </div>
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
                                <th scope="col">Fecha de Registro</th>
                                <th scope="col">Consentimiento</th>
                                <th scope="col">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $subjects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subj): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs flex-shrink-0 me-2">
                                            <span class="avatar-title bg-light text-primary rounded fs-13">
                                                <?php echo e($subj->tipo == 'persona_fisica' ? 'PF' : 'PM'); ?>

                                            </span>
                                        </div>
                                        <span class="fw-semibold"><?php echo e($subj->name_or_company); ?></span>
                                    </div>
                                </td>
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
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="ri-check-line align-middle me-1"></i> Autorizado
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo e(route('tenant.subjects.show', $subj->id)); ?>" class="btn btn-sm btn-soft-primary">
                                        <i class="ri-eye-line"></i> Abrir Expediente
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay sujetos registrados en la base de datos.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <?php echo e($subjects->links()); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Atlas\resources\views/tenant/subjects/index.blade.php ENDPATH**/ ?>