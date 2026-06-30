<?php $__env->startSection('title'); ?> Clientes <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Administración de Clientes</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('superadmin.dashboard')); ?>">Super Admin</a></li>
                    <li class="breadcrumb-item active">Clientes</li>
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
                <h5 class="card-title mb-0 flex-grow-1">Listado de Clientes Registrados</h5>
                <div class="flex-shrink-0">
                    <a href="<?php echo e(route('superadmin.tenants.create')); ?>" class="btn btn-primary btn-sm"><i class="ri-add-line align-bottom me-1"></i> Registrar Nuevo Cliente</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-nowrap table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nombre Comercial</th>
                                <th scope="col">Usuarios</th>
                                <th scope="col">Límite Consultas / Mes</th>
                                <th scope="col">Estatus</th>
                                <th scope="col">Creado el</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $tenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="fw-semibold">#<?php echo e($tenant->id); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs flex-shrink-0 me-2">
                                            <span class="avatar-title bg-primary-subtle rounded text-primary fs-14">
                                                <?php echo e(substr($tenant->name, 0, 2)); ?>

                                            </span>
                                        </div>
                                        <span class="fw-medium"><?php echo e($tenant->name); ?></span>
                                    </div>
                                </td>
                                <td><a href="<?php echo e(route('superadmin.users.index', ['tenant_id' => $tenant->id])); ?>" class="badge bg-primary text-white fs-12"><?php echo e($tenant->users_count); ?> usuarios</a></td>
                                <td><?php echo e($tenant->limite_consultas_mensual); ?></td>
                                <td>
                                    <?php if($tenant->activo): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo/Suspendido</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($tenant->created_at->format('d/m/Y H:i')); ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="<?php echo e(route('superadmin.tenants.edit', $tenant->id)); ?>" class="btn btn-sm btn-soft-primary">
                                            <i class="ri-edit-line"></i> Editar
                                        </a>
                                        <form action="<?php echo e(route('superadmin.tenants.destroy', $tenant->id)); ?>" method="POST" onsubmit="return confirm('¿Estás seguro de cambiar el estatus de este cliente?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-sm <?php echo e($tenant->activo ? 'btn-soft-danger' : 'btn-soft-success'); ?>">
                                                <i class="<?php echo e($tenant->activo ? 'ri-close-circle-line' : 'ri-checkbox-circle-line'); ?>"></i>
                                                <?php echo e($tenant->activo ? 'Desactivar' : 'Activar'); ?>

                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay clientes registrados.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <?php echo e($tenants->links()); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Atlas\resources\views/superadmin/tenants/index.blade.php ENDPATH**/ ?>