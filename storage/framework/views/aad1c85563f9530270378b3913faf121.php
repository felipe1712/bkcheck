<?php $__env->startSection('title'); ?> Proyecto: <?php echo e($project->name); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Proyecto: <?php echo e($project->name); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('tenant.dashboard')); ?>">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('tenant.projects.index')); ?>">Proyectos</a></li>
                    <li class="breadcrumb-item active">Detalle</li>
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
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Descripción del Proyecto</h5>
            </div>
            <div class="card-body">
                <p class="text-muted fs-14"><?php echo e($project->description ?: 'Sin descripción registrada.'); ?></p>
                <div class="mt-3 text-muted">
                    <p class="mb-1"><i class="ri-calendar-line align-middle me-1"></i> Creado el: <?php echo e($project->created_at->format('d/m/Y H:i')); ?></p>
                    <p class="mb-0"><i class="ri-user-line align-middle me-1"></i> ID de Proyecto: #<?php echo e($project->id); ?></p>
                </div>
                <div class="mt-4">
                    <a href="<?php echo e(route('tenant.projects.edit', $project->id)); ?>" class="btn btn-soft-primary w-100"><i class="ri-edit-line me-1"></i> Editar Proyecto</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header border-0 align-items-center d-flex">
                <h5 class="card-title mb-0 flex-grow-1">Sujetos Investigados en este Proyecto</h5>
                <div class="flex-shrink-0">
                    <a href="<?php echo e(route('tenant.subjects.create', ['project_id' => $project->id])); ?>" class="btn btn-primary btn-sm">
                        <i class="ri-user-add-line align-bottom me-1"></i> Registrar Sujeto
                    </a>
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
                                <th scope="col">Consentimiento</th>
                                <th scope="col">Acciones</th>
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
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="ri-check-line align-middle me-1"></i> Otorgado
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="<?php echo e(route('tenant.subjects.show', $subj->id)); ?>" class="btn btn-sm btn-soft-primary">
                                            <i class="ri-eye-line"></i> Abrir Expediente
                                        </a>
                                        <form action="<?php echo e(route('tenant.subjects.destroy', $subj->id)); ?>" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este sujeto de investigación?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-sm btn-soft-danger">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay sujetos registrados en este proyecto.</td>
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

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Atlas\resources\views/tenant/projects/show.blade.php ENDPATH**/ ?>