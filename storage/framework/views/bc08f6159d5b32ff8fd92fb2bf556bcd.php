<?php $__env->startSection('title'); ?> Gestión de Usuarios <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Administración de Usuarios</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('superadmin.dashboard')); ?>">Super Admin</a></li>
                    <li class="breadcrumb-item active">Usuarios</li>
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

<?php if($errors->any()): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><?php echo e($error); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-body">
                <form action="<?php echo e(route('superadmin.users.index')); ?>" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label for="tenant_filter" class="form-label fw-semibold">Filtrar por Cliente</label>
                        <select name="tenant_id" id="tenant_filter" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Todos los Clientes --</option>
                            <?php $__currentLoopData = $tenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($tenant->id); ?>" <?php echo e($tenantId == $tenant->id ? 'selected' : ''); ?>>
                                    <?php echo e($tenant->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end mt-4">
                        <?php if($tenantId): ?>
                            <a href="<?php echo e(route('superadmin.users.index')); ?>" class="btn btn-light w-100">
                                <i class="ri-refresh-line align-bottom me-1"></i> Limpiar Filtro
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 align-items-center d-flex">
                <h5 class="card-title mb-0 flex-grow-1">Lista de Todos los Usuarios</h5>
                <div class="flex-shrink-0">
                    <a href="<?php echo e(route('superadmin.users.create')); ?>" class="btn btn-primary btn-sm"><i class="ri-add-line align-bottom me-1"></i> Crear Nuevo Usuario</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-nowrap table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Email</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Rol</th>
                                <th scope="col">Estatus</th>
                                <th scope="col">Fecha de Registro</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="fw-semibold">#<?php echo e($user->id); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs flex-shrink-0 me-2">
                                            <span class="avatar-title bg-primary-subtle rounded-circle text-primary fs-14">
                                                <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                                            </span>
                                        </div>
                                        <span class="fw-semibold"><?php echo e($user->name); ?></span>
                                    </div>
                                </td>
                                <td><?php echo e($user->email); ?></td>
                                <td>
                                    <?php if($user->tenant): ?>
                                        <span class="badge bg-light text-dark border"><?php echo e($user->tenant->name); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">Global (Super Admin)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $__currentLoopData = $user->roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($role->name == 'super_admin'): ?>
                                            <span class="badge bg-dark text-white">Super Administrador</span>
                                        <?php elseif($role->name == 'tenant_admin'): ?>
                                            <span class="badge bg-info-subtle text-info">Administrador Cliente</span>
                                        <?php elseif($role->name == 'investigador'): ?>
                                            <span class="badge bg-success-subtle text-success">Investigador</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary"><?php echo e($role->name); ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </td>
                                <td>
                                    <?php if($user->activo): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Bloqueado</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A'); ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="<?php echo e(route('superadmin.users.edit', $user->id)); ?>" class="btn btn-sm btn-soft-primary">
                                            <i class="ri-edit-line"></i> Editar
                                        </a>

                                        <?php if($user->id !== Auth::id()): ?>
                                        <!-- Toggle Status -->
                                        <form action="<?php echo e(route('superadmin.users.toggle-status', $user->id)); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>
                                            <button type="submit" class="btn btn-sm <?php echo e($user->activo ? 'btn-soft-warning' : 'btn-soft-success'); ?>" 
                                                    title="<?php echo e($user->activo ? 'Bloquear usuario' : 'Desbloquear usuario'); ?>">
                                                <i class="<?php echo e($user->activo ? 'ri-user-unfollow-line' : 'ri-user-follow-line'); ?>"></i> 
                                                <?php echo e($user->activo ? 'Bloquear' : 'Desbloquear'); ?>

                                            </button>
                                        </form>

                                        <!-- Delete -->
                                        <form action="<?php echo e(route('superadmin.users.destroy', $user->id)); ?>" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este usuario permanentemente?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-sm btn-soft-danger">
                                                <i class="ri-delete-bin-line"></i> Eliminar
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-muted fs-12 italic">Tu usuario</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No se encontraron usuarios.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <?php echo e($users->links()); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Atlas\resources\views/superadmin/users/index.blade.php ENDPATH**/ ?>