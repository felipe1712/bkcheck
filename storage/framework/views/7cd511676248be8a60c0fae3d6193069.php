<?php $__env->startSection('title'); ?> Registrar Sujeto <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Registrar Sujeto de Investigación</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('tenant.dashboard')); ?>">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('tenant.subjects.index')); ?>">Sujetos</a></li>
                    <li class="breadcrumb-item active">Registrar</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-xxl-8">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Proceso de Registro y Consentimiento Obligatorio (Wizard)</h4>
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('tenant.subjects.store')); ?>" method="POST" enctype="multipart/form-data" id="subjectForm">
                    <?php echo csrf_field(); ?>

                    <!-- Step 1: Datos Básicos del Sujeto -->
                    <div id="step-1">
                        <div class="d-flex align-items-center mb-4 border-bottom pb-2">
                            <span class="avatar-title bg-primary text-white rounded-circle fs-16 me-2" style="width: 28px; height: 28px; display: inline-flex; justify-content: center; align-items: center;">1</span>
                            <h5 class="text-primary mb-0">Información de Identidad del Sujeto</h5>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="project_id" class="form-label">Proyecto Asociado</label>
                                <select class="form-select <?php $__errorArgs = ['project_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="project_id" name="project_id" required>
                                    <option value="" disabled selected>Seleccione un proyecto...</option>
                                    <?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $proj): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($proj->id); ?>" <?php echo e((old('project_id') == $proj->id || $selectedProjectId == $proj->id) ? 'selected' : ''); ?>>
                                            <?php echo e($proj->name); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['project_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="col-md-6">
                                <label for="tipo" class="form-label">Tipo de Contribuyente</label>
                                <select class="form-select <?php $__errorArgs = ['tipo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="tipo" name="tipo" onchange="toggleSubjectTypeFields()" required>
                                    <option value="persona_fisica" <?php echo e(old('tipo') == 'persona_fisica' ? 'selected' : ''); ?>>Persona Física</option>
                                    <option value="persona_moral" <?php echo e(old('tipo', 'persona_moral') == 'persona_moral' ? 'selected' : ''); ?>>Persona Moral</option>
                                </select>
                                <?php $__errorArgs = ['tipo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name_or_company" class="form-label" id="nameLabel">Razón Social / Nombre Legal</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['name_or_company'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="name_or_company" name="name_or_company" value="<?php echo e(old('name_or_company')); ?>" placeholder="Ej: Aceros de México S.A. de C.V." required>
                                <?php $__errorArgs = ['name_or_company'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="col-md-6">
                                <label for="rfc" class="form-label">RFC (Registro Federal de Contribuyentes)</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['rfc'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="rfc" name="rfc" value="<?php echo e(old('rfc')); ?>" placeholder="Ej: AME120304XYZ" style="text-transform: uppercase;" required>
                                <small class="text-muted">12 dígitos para empresas, 13 dígitos para personas físicas.</small>
                                <?php $__errorArgs = ['rfc'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row mb-3" id="curpGroup" style="display: none;">
                            <div class="col-md-6">
                                <label for="curp" class="form-label">CURP (Clave Única de Registro de Población)</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['curp'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="curp" name="curp" value="<?php echo e(old('curp')); ?>" placeholder="Ej: PEJU900101HDFLNR01" style="text-transform: uppercase;">
                                <small class="text-muted">Requerido únicamente para Persona Física (18 caracteres).</small>
                                <?php $__errorArgs = ['curp'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label">Dirección Fiscal / Domicilio</label>
                            <textarea class="form-control <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="address" name="address" rows="2" placeholder="Calle, Número, Colonia, C.P., Ciudad y Estado..."><?php echo e(old('address')); ?></textarea>
                            <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <!-- Step 2: Consentimiento Legal Obligatorio -->
                    <div id="step-2" class="mt-4">
                        <div class="d-flex align-items-center mb-4 border-bottom pb-2">
                            <span class="avatar-title bg-primary text-white rounded-circle fs-16 me-2" style="width: 28px; height: 28px; display: inline-flex; justify-content: center; align-items: center;">2</span>
                            <h5 class="text-primary mb-0">Cumplimiento de Privacidad y Consentimiento Expreso</h5>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="consent_legal_basis" class="form-label">Base Legal / Finalidad de la Consulta</label>
                                <select class="form-select <?php $__errorArgs = ['consent_legal_basis'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="consent_legal_basis" name="consent_legal_basis" required>
                                    <option value="" disabled selected>Seleccione la base legal...</option>
                                    <option value="Alta de Proveedores (Cumplimiento Comercial/KYB)" <?php echo e(old('consent_legal_basis') == 'Alta de Proveedores (Cumplimiento Comercial/KYB)' ? 'selected' : ''); ?>>Alta de Proveedores (Cumplimiento Comercial/KYB)</option>
                                    <option value="Proceso de Selección de Personal (Candidatos)" <?php echo e(old('consent_legal_basis') == 'Proceso de Selección de Personal (Candidatos)' ? 'selected' : ''); ?>>Proceso de Selección de Personal (Candidatos)</option>
                                    <option value="Auditoría Interna y Prevención de Lavado de Dinero (PLD)" <?php echo e(old('consent_legal_basis') == 'Auditoría Interna y Prevención de Lavado de Dinero (PLD)' ? 'selected' : ''); ?>>Auditoría Interna y Prevención de Lavado de Dinero (PLD)</option>
                                    <option value="Evaluación de Riesgo de Crédito (Financiamiento)" <?php echo e(old('consent_legal_basis') == 'Evaluación de Riesgo de Crédito (Financiamiento)' ? 'selected' : ''); ?>>Evaluación de Riesgo de Crédito (Financiamiento)</option>
                                    <option value="Otros fines comerciales legítimos" <?php echo e(old('consent_legal_basis') == 'Otros fines comerciales legítimos' ? 'selected' : ''); ?>>Otros fines comerciales legítimos</option>
                                </select>
                                <?php $__errorArgs = ['consent_legal_basis'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="col-md-6">
                                <label for="consent_document" class="form-label">Subir Carta de Consentimiento Firmada (PDF / Imagen)</label>
                                <input type="file" class="form-control <?php $__errorArgs = ['consent_document'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="consent_document" name="consent_document" accept="application/pdf,image/*">
                                <small class="text-muted">Suba el formato firmado por el sujeto autorizando la búsqueda (Max 5MB).</small>
                                <?php $__errorArgs = ['consent_document'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <!-- Checkbox Obligatorio -->
                        <div class="form-check mb-4 bg-light p-3 rounded border border-warning-subtle">
                            <input class="form-check-input <?php $__errorArgs = ['consent_granted'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="checkbox" value="1" id="consent_granted" name="consent_granted" <?php echo e(old('consent_granted') ? 'checked' : ''); ?> required style="margin-left: 0; margin-right: 0.5rem; float: left;">
                            <label class="form-check-label text-wrap" for="consent_granted" style="font-weight: 500; font-size: 13.5px; padding-left: 20px; display: block;">
                                Confirmo que el sujeto (persona física o moral) ha otorgado su consentimiento expreso, libre e informado para realizar esta búsqueda de antecedentes y consultas de registros públicos bajo el amparo de la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP).
                            </label>
                            <?php $__errorArgs = ['consent_granted'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback d-block mt-2"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo e($selectedProjectId ? route('tenant.projects.show', $selectedProjectId) : route('tenant.subjects.index')); ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn"><i class="ri-check-line align-bottom me-1"></i> Registrar y Validar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSubjectTypeFields() {
        var tipo = document.getElementById('tipo').value;
        var curpGroup = document.getElementById('curpGroup');
        var curpInput = document.getElementById('curp');
        var nameLabel = document.getElementById('nameLabel');

        if (tipo === 'persona_fisica') {
            curpGroup.style.display = 'block';
            curpInput.setAttribute('required', 'required');
            nameLabel.innerText = 'Nombre Completo (Persona Física)';
            document.getElementById('name_or_company').placeholder = 'Ej: Juan Pérez López';
            document.getElementById('rfc').placeholder = 'Ej: PELJ900101XYZ';
        } else {
            curpGroup.style.display = 'none';
            curpInput.removeAttribute('required');
            curpInput.value = '';
            nameLabel.innerText = 'Razón Social / Nombre Legal';
            document.getElementById('name_or_company').placeholder = 'Ej: Aceros de México S.A. de C.V.';
            document.getElementById('rfc').placeholder = 'Ej: AME120304XYZ';
        }
    }

    // Call on load to set fields
    document.addEventListener("DOMContentLoaded", function() {
        toggleSubjectTypeFields();
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Atlas\resources\views/tenant/subjects/create.blade.php ENDPATH**/ ?>