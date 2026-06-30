<?php $__env->startSection('title'); ?> Expediente: <?php echo e($subject->name_or_company); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Expediente de Sujeto de Investigación</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('tenant.dashboard')); ?>">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('tenant.projects.show', $subject->project_id)); ?>">Proyecto</a></li>
                    <li class="breadcrumb-item active">Expediente</li>
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

<?php
    $rfcQuery = $queries->firstWhere('source_type', 'rfc');
    $csdQuery = $queries->firstWhere('source_type', 'csd');
    $sigerQuery = $queries->firstWhere('source_type', 'siger');
    $satListasQuery = $queries->firstWhere('source_type', 'sat_listas');
    $marcasQuery = $queries->firstWhere('source_type', 'marcas');

    $hasCompletedQueries = $queries->where('status', 'completed')->isNotEmpty();
    $isProcessing = $queries->whereIn('status', ['pending', 'processing'])->isNotEmpty();

    // Check SAT list alert
    $hasSatAlert = false;
    if ($satListasQuery && $satListasQuery->status === 'completed' && isset($satListasQuery->result->processed_data['en_lista_69b'])) {
        $hasSatAlert = (bool) $satListasQuery->result->processed_data['en_lista_69b'];
    }
?>

<?php if($hasSatAlert): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow" role="alert">
    <div class="d-flex align-items-center">
        <div class="flex-shrink-0 me-3">
            <i class="ri-error-warning-fill fs-24 align-middle text-danger"></i>
        </div>
        <div class="flex-grow-1">
            <h5 class="alert-heading text-danger fw-semibold">¡ALERTA DE RIESGO CRÍTICO!</h5>
            <p class="mb-0 fs-13">El sujeto <strong><?php echo e($subject->name_or_company); ?></strong> ha sido encontrado en las listas del SAT del artículo <strong>69-B (Facturación Simulada - EFOS/EDOS)</strong> con estatus de <strong><?php echo e($satListasQuery->result->processed_data['estatus_69b'] ?? 'Presunto'); ?></strong>. Proceda con precaución y revise los detalles técnicos de la auditoría.</p>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- Demographic / Details Panel -->
    <div class="col-xl-4">
        <div class="card card-profile">
            <div class="card-body p-4">
                <div class="text-center">
                    <div class="avatar-md mx-auto mb-3">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24 shadow material-shadow">
                            <?php echo e($subject->tipo == 'persona_fisica' ? 'PF' : 'PM'); ?>

                        </span>
                    </div>
                    <h5 class="fs-16 mb-1"><?php echo e($subject->name_or_company); ?></h5>
                    <p class="text-muted mb-4 text-capitalize"><?php echo e(str_replace('_', ' ', $subject->tipo)); ?></p>
                </div>
                
                <div class="table-responsive table-card">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="fw-semibold" style="width: 30%">RFC:</td>
                                <td><code><?php echo e($subject->rfc); ?></code></td>
                            </tr>
                            <?php if($subject->curp): ?>
                            <tr>
                                <td class="fw-semibold">CURP:</td>
                                <td><code><?php echo e($subject->curp); ?></code></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="fw-semibold">Domicilio:</td>
                                <td><span class="text-muted"><?php echo e($subject->address ?: 'No provisto'); ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr>

                <h6 class="text-uppercase fs-12 mb-3">Consentimiento Legal</h6>
                <div class="table-responsive table-card">
                    <table class="table table-borderless mb-0 fs-13">
                        <tbody>
                            <tr>
                                <td class="fw-semibold" style="width: 30%">Otorgado:</td>
                                <td><span class="badge bg-success-subtle text-success">Sí, Expreso</span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Fecha:</td>
                                <td><?php echo e($subject->consent_date ? $subject->consent_date->format('d/m/Y H:i') : 'No registrado'); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Finalidad:</td>
                                <td><span class="text-muted"><?php echo e($subject->consent_legal_basis); ?></span></td>
                            </tr>
                            <?php if($subject->consent_document_path): ?>
                            <tr>
                                <td class="fw-semibold">Documento:</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary"><i class="ri-checkbox-circle-line"></i> Carta Adjunta</span>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 gap-2 d-flex flex-column">
                    <a href="<?php echo e(route('tenant.subjects.edit', $subject->id)); ?>" class="btn btn-soft-primary w-100"><i class="ri-edit-box-line me-1"></i> Editar Ficha del Sujeto</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Investigation Sources Tabs Panel -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header border-0 align-items-center d-flex">
                <h5 class="card-title mb-0 flex-grow-1">Fuentes de Verificación de Antecedentes</h5>
                <div class="flex-shrink-0 d-flex gap-2">
                    <?php if($hasCompletedQueries): ?>
                        <a href="<?php echo e(route('tenant.subjects.report', $subject->id)); ?>" target="_blank" class="btn btn-info btn-sm">
                            <i class="ri-file-pdf-line align-bottom me-1"></i> Descargar PDF
                        </a>
                    <?php endif; ?>

                    <?php if($queries->isEmpty() || $isProcessing): ?>
                    <form action="<?php echo e(route('tenant.subjects.investigate', $subject->id)); ?>" method="POST" id="startInvestigationForm">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-danger btn-sm" id="runInvestigationBtn" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                            <?php if($isProcessing): ?>
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Investigando...
                            <?php else: ?>
                                <i class="ri-play-circle-line align-bottom me-1"></i> Iniciar Investigación
                            <?php endif; ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs nav-tabs-custom nav-success nav-justified mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#rfc-tab" role="tab">
                            Validación RFC
                            <?php if($rfcQuery): ?>
                                <span class="badge rounded-pill bg-<?php echo e($rfcQuery->status === 'completed' ? 'success' : ($rfcQuery->status === 'failed' ? 'danger' : 'warning')); ?> fs-10 ms-1"><?php echo e($rfcQuery->status); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#csd-tab" role="tab">
                            Sellos CSD
                            <?php if($csdQuery): ?>
                                <span class="badge rounded-pill bg-<?php echo e($csdQuery->status === 'completed' ? 'success' : ($csdQuery->status === 'failed' ? 'danger' : 'warning')); ?> fs-10 ms-1"><?php echo e($csdQuery->status); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo e($subject->tipo === 'persona_fisica' ? 'disabled text-decoration-line-through' : ''); ?>" data-bs-toggle="tab" href="#siger-tab" role="tab">
                            Registro SIGER
                            <?php if($sigerQuery): ?>
                                <span class="badge rounded-pill bg-<?php echo e($sigerQuery->status === 'completed' ? 'success' : ($sigerQuery->status === 'failed' ? 'danger' : 'warning')); ?> fs-10 ms-1"><?php echo e($sigerQuery->status); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#sat69-tab" role="tab">
                            Listas SAT 69/B
                            <?php if($satListasQuery): ?>
                                <span class="badge rounded-pill bg-<?php echo e($satListasQuery->status === 'completed' ? 'success' : ($satListasQuery->status === 'failed' ? 'danger' : 'warning')); ?> fs-10 ms-1"><?php echo e($satListasQuery->status); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#impi-tab" role="tab">
                            Marcas IMPI
                            <?php if($marcasQuery): ?>
                                <span class="badge rounded-pill bg-<?php echo e($marcasQuery->status === 'completed' ? 'success' : ($marcasQuery->status === 'failed' ? 'danger' : 'warning')); ?> fs-10 ms-1"><?php echo e($marcasQuery->status); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>

                <!-- Tab panes -->
                <div class="tab-content text-muted">
                    
                    <!-- VALIDACION RFC TAB -->
                    <div class="tab-pane active" id="rfc-tab" role="tabpanel">
                        <?php if(!$rfcQuery): ?>
                            <div class="text-center py-4">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:72px;height:72px"></lord-icon>
                                <h5 class="mt-4">Validación del Registro Federal de Contribuyentes</h5>
                                <p class="text-muted">La consulta de validación de este RFC ante el SAT aún no se ha iniciado.</p>
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'rfc'])); ?>" method="POST" class="mt-3">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-primary" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-play-circle-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        <?php elseif($rfcQuery->status === 'pending' || $rfcQuery->status === 'processing'): ?>
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                                <h5 class="mt-4">Procesando consulta...</h5>
                                <p class="text-muted">Estamos validando los datos del RFC ante el SAT. Por favor espere...</p>
                            </div>
                        <?php elseif($rfcQuery->status === 'failed'): ?>
                            <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="alert-heading text-danger fw-semibold">Error al consultar la fuente</h6>
                                    <p class="mb-0"><?php echo e($rfcQuery->error_message); ?></p>
                                </div>
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'rfc'])); ?>" method="POST" class="flex-shrink-0">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-danger" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        <?php elseif($rfcQuery->status === 'completed' && $rfcQuery->result): ?>
                            <?php $rfcData = $rfcQuery->result->processed_data; ?>
                            <div class="card border-0">
                                <div class="card-header bg-light-subtle pb-2 border-0 d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 text-primary fw-semibold">Información Oficial SAT</h6>
                                    <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'rfc'])); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-sm btn-soft-primary" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                            <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                        </button>
                                    </form>
                                </div>
                                <div class="card-body p-0 pt-2">
                                    <table class="table table-bordered align-middle">
                                        <tbody>
                                            <tr>
                                                <td class="fw-semibold bg-light" style="width: 30%">RFC:</td>
                                                <td><code><?php echo e($rfcData['rfc'] ?? 'N/A'); ?></code></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold bg-light">Válido en SAT:</td>
                                                <td>
                                                    <?php if($rfcData['valido'] ?? false): ?>
                                                        <span class="badge bg-success-subtle text-success">Sí, Vigente</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger-subtle text-danger">No Válido</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold bg-light">Situación SAT:</td>
                                                <td>
                                                    <span class="badge bg-info-subtle text-info"><?php echo e($rfcData['situacion'] ?? 'ACTIVO'); ?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold bg-light">Razón Social:</td>
                                                <td class="fw-semibold text-dark"><?php echo e($rfcData['razon_social'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold bg-light">Tipo de Persona:</td>
                                                <td><?php echo e($rfcData['tipo_persona'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <?php if(isset($rfcData['curp']) && $rfcData['curp']): ?>
                                            <tr>
                                                <td class="fw-semibold bg-light">CURP Validado:</td>
                                                <td><code><?php echo e($rfcData['curp']); ?></code></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- CSD TAB -->
                    <div class="tab-pane" id="csd-tab" role="tabpanel">
                        <?php if(!$csdQuery): ?>
                            <div class="text-center py-4">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:72px;height:72px"></lord-icon>
                                <h5 class="mt-4">Certificados de Sello Digital (CSD / FIEL)</h5>
                                <p class="text-muted">La recuperación de certificados vigentes y caducos de este RFC aún no se ha iniciado.</p>
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'csd'])); ?>" method="POST" class="mt-3">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-primary" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-play-circle-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        <?php elseif($csdQuery->status === 'pending' || $csdQuery->status === 'processing'): ?>
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                                <h5 class="mt-4">Procesando consulta...</h5>
                                <p class="text-muted">Recuperando el historial de sellos digitales del RFC. Espere...</p>
                            </div>
                        <?php elseif($csdQuery->status === 'failed'): ?>
                            <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="alert-heading text-danger fw-semibold">Error al consultar la fuente</h6>
                                    <p class="mb-0"><?php echo e($csdQuery->error_message); ?></p>
                                </div>
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'csd'])); ?>" method="POST" class="flex-shrink-0">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-danger" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        <?php elseif($csdQuery->status === 'completed' && $csdQuery->result): ?>
                            <?php $certs = $csdQuery->result->processed_data['certificados'] ?? []; ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-primary mb-0 fw-semibold">Historial de Certificados Detectados</h6>
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'csd'])); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-soft-primary" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                            <?php if(empty($certs)): ?>
                                <div class="text-center text-muted py-3">No se encontraron sellos o firmas electrónicas vinculadas a este RFC.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Número de Serie</th>
                                                <th>Tipo</th>
                                                <th>Estatus</th>
                                                <th>Inicio de Vigencia</th>
                                                <th>Fin de Vigencia</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $certs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td><code><?php echo e($cert['numero_serie'] ?? ''); ?></code></td>
                                                <td><span class="badge bg-secondary-subtle text-secondary"><?php echo e($cert['tipo'] ?? 'CSD'); ?></span></td>
                                                <td>
                                                    <span class="badge bg-<?php echo e(($cert['estado'] ?? '') === 'ACTIVO' ? 'success' : 'danger'); ?>-subtle text-<?php echo e(($cert['estado'] ?? '') === 'ACTIVO' ? 'success' : 'danger'); ?>">
                                                        <?php echo e($cert['estado'] ?? 'CADUCO'); ?>

                                                    </span>
                                                </td>
                                                <td><?php echo e(isset($cert['fecha_inicio']) ? \Carbon\Carbon::parse($cert['fecha_inicio'])->format('d/m/Y H:i') : 'N/A'); ?></td>
                                                <td><?php echo e(isset($cert['fecha_fin']) ? \Carbon\Carbon::parse($cert['fecha_fin'])->format('d/m/Y H:i') : 'N/A'); ?></td>
                                            </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- SIGER TAB -->
                    <div class="tab-pane" id="siger-tab" role="tabpanel">
                        <?php if($subject->tipo === 'persona_fisica'): ?>
                            <div class="alert alert-warning border-0">
                                <h6 class="alert-heading text-warning fw-semibold"><i class="ri-alert-line align-middle me-1"></i> Fuente Excluida</h6>
                                <p class="mb-0">La consulta del Registro Público de Comercio (SIGER) no aplica para Personas Físicas. Esta fuente está restringida a Sociedades Mercantiles (Personas Morales).</p>
                            </div>
                        <?php elseif(!$sigerQuery): ?>
                            <div class="text-center py-4">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:72px;height:72px"></lord-icon>
                                <h5 class="mt-4">Registro Público de Comercio (SIGER)</h5>
                                <p class="text-muted">La búsqueda de sociedades mercantiles y participación accionaria aún no se ha iniciado.</p>
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'siger'])); ?>" method="POST" class="mt-3">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-primary" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-play-circle-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        <?php elseif($sigerQuery->status === 'pending' || $sigerQuery->status === 'processing'): ?>
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                                <h5 class="mt-4">Procesando consulta...</h5>
                                <p class="text-muted">Buscando actas mercantiles y composiciones de socios ante la Secretaría de Economía. Espere...</p>
                            </div>
                        <?php elseif($sigerQuery->status === 'failed'): ?>
                            <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="alert-heading text-danger fw-semibold">Error al consultar la fuente</h6>
                                    <p class="mb-0"><?php echo e($sigerQuery->error_message); ?></p>
                                </div>
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'siger'])); ?>" method="POST" class="flex-shrink-0">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-danger" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        <?php elseif($sigerQuery->status === 'completed' && $sigerQuery->result): ?>
                            <?php $results = $sigerQuery->result->processed_data['resultados'] ?? []; ?>
                            <div class="d-flex justify-content-end mb-3">
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'siger'])); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-soft-primary" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                            <?php if(empty($results)): ?>
                                <div class="text-center text-muted py-3">No se localizaron registros comerciales en SIGER vinculados a esta Razón Social.</div>
                            <?php else: ?>
                                <?php $__currentLoopData = $results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $res): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="card border border-dashed mb-3">
                                    <div class="card-header bg-light-subtle align-items-center d-flex pb-2">
                                        <h6 class="card-title mb-0 flex-grow-1 text-primary">Folio Mercantil Electrónico (FME): #<?php echo e($res['fme'] ?? ''); ?></h6>
                                        <div class="flex-shrink-0">
                                            <span class="badge bg-success-subtle text-success"><?php echo e($res['entidad_federativa'] ?? 'CDMX'); ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <div class="col-sm-6">
                                                <span class="text-muted">Fecha de Constitución:</span>
                                                <span class="fw-semibold d-block text-dark"><?php echo e(isset($res['fecha_constitucion']) ? \Carbon\Carbon::parse($res['fecha_constitucion'])->format('d/m/Y') : 'N/A'); ?></span>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted">Capital Social de Inicio:</span>
                                                <span class="fw-semibold d-block text-dark">$<?php echo e(number_format($res['capital_social'] ?? 0, 2)); ?> MXN</span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <span class="text-muted">Objeto Social Constituido:</span>
                                            <p class="text-muted fs-12 mb-0" style="text-align: justify;"><?php echo e($res['objeto_social'] ?? 'N/A'); ?></p>
                                        </div>

                                        <h6 class="text-uppercase text-muted fs-11 mb-2">Socios y Accionistas Registrados</h6>
                                        <div class="table-responsive table-card">
                                            <table class="table table-sm table-nowrap mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Nombre Completo</th>
                                                        <th>Participación</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $__currentLoopData = $res['socios'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $socio): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr>
                                                        <td class="fw-semibold text-dark"><?php echo e($socio['nombre'] ?? ''); ?></td>
                                                        <td><span class="badge bg-primary-subtle text-primary"><?php echo e($socio['participacion'] ?? ''); ?></span></td>
                                                    </tr>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- LISTAS SAT TAB -->
                    <div class="tab-pane" id="sat69-tab" role="tabpanel">
                        <?php if(!$satListasQuery): ?>
                            <div class="text-center py-4">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:72px;height:72px"></lord-icon>
                                <h5 class="mt-4">Cumplimiento Fiscal: EFOS / EDOS (Listas 69 y 69-B)</h5>
                                <p class="text-muted">La revisión de listados negros de contribuyentes incumplidos o simuladores aún no se ha iniciado.</p>
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'sat_listas'])); ?>" method="POST" class="mt-3">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-primary" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-play-circle-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        <?php elseif($satListasQuery->status === 'pending' || $satListasQuery->status === 'processing'): ?>
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                                <h5 class="mt-4">Procesando consulta...</h5>
                                <p class="text-muted">Buscando reportes negativos o boletines en la lista negra del SAT. Espere...</p>
                            </div>
                        <?php elseif($satListasQuery->status === 'failed'): ?>
                            <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="alert-heading text-danger fw-semibold">Error al consultar la fuente</h6>
                                    <p class="mb-0"><?php echo e($satListasQuery->error_message); ?></p>
                                </div>
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'sat_listas'])); ?>" method="POST" class="flex-shrink-0">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-danger" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        <?php elseif($satListasQuery->status === 'completed' && $satListasQuery->result): ?>
                            <?php $listData = $satListasQuery->result->processed_data; ?>
                            <div class="d-flex justify-content-end mb-3">
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'sat_listas'])); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-soft-primary" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card border">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-2 text-uppercase fs-11">Lista 69 (Créditos condonados, exigibles o cancelados)</h6>
                                            <?php if($listData['en_lista_69'] ?? false): ?>
                                                <span class="badge bg-danger-subtle text-danger fs-12 p-2 w-100 text-center"><i class="ri-error-warning-line me-1"></i> Contribuyente Exceptuado</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success fs-12 p-2 w-100 text-center"><i class="ri-checkbox-circle-line me-1"></i> Sin Incidencias Registradas</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card border">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-2 text-uppercase fs-11">Lista 69-B (EFOS - Emisores de Operaciones Simuladas)</h6>
                                            <?php if($listData['en_lista_69b'] ?? false): ?>
                                                <span class="badge bg-danger-subtle text-danger fs-12 p-2 w-100 text-center"><i class="ri-alert-line me-1"></i> Boletinado Facturación Simulada</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success fs-12 p-2 w-100 text-center"><i class="ri-checkbox-circle-line me-1"></i> Sin Incidencias Registradas</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if($listData['en_lista_69b'] ?? false): ?>
                            <div class="card border border-danger border-dashed">
                                <div class="card-header bg-danger-subtle text-danger pb-2">
                                    <h6 class="card-title mb-0 text-danger fw-semibold"><i class="ri-error-warning-fill me-1 align-middle"></i> Detalles del Boletín Oficial</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-semibold bg-light" style="width: 30%">Estatus 69-B:</td>
                                                <td><span class="badge bg-warning text-dark"><?php echo e($listData['estatus_69b'] ?? 'Presunto'); ?></span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold bg-light">Número de Oficio:</td>
                                                <td><code><?php echo e($listData['oficio_oficial'] ?? 'N/A'); ?></code></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold bg-light">Publicación DOF:</td>
                                                <td><?php echo e($listData['fecha_publicacion'] ?? 'N/A'); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- IMPI TAB -->
                    <div class="tab-pane" id="impi-tab" role="tabpanel">
                        <?php if(!$marcasQuery): ?>
                            <div class="text-center py-4">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:72px;height:72px"></lord-icon>
                                <h5 class="mt-4">Propiedad Industrial (IMPI)</h5>
                                <p class="text-muted">La búsqueda de marcas o patentes registradas a nombre de este sujeto aún no se ha iniciado.</p>
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'marcas'])); ?>" method="POST" class="mt-3">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-primary" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-play-circle-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        <?php elseif($marcasQuery->status === 'pending' || $marcasQuery->status === 'processing'): ?>
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                                <h5 class="mt-4">Procesando consulta...</h5>
                                <p class="text-muted">Buscando registros marcarios y denominaciones comerciales en las bases de datos del IMPI. Espere...</p>
                            </div>
                        <?php elseif($marcasQuery->status === 'failed'): ?>
                            <div class="alert alert-danger border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="alert-heading text-danger fw-semibold">Error al consultar la fuente</h6>
                                    <p class="mb-0"><?php echo e($marcasQuery->error_message); ?></p>
                                </div>
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'marcas'])); ?>" method="POST" class="flex-shrink-0">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-danger" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                        <?php elseif($marcasQuery->status === 'completed' && $marcasQuery->result): ?>
                            <?php $marcas = $marcasQuery->result->processed_data['marcas'] ?? []; ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-primary mb-0 fw-semibold">Marcas Registradas / Solicitudes a su Nombre</h6>
                                <form action="<?php echo e(route('tenant.subjects.investigate.source', [$subject->id, 'marcas'])); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-soft-primary" <?php echo e($isProcessing ? 'disabled' : ''); ?>>
                                        <i class="ri-refresh-line align-bottom me-1"></i> Re-Consultar
                                    </button>
                                </form>
                            </div>
                            <?php if(empty($marcas)): ?>
                                <div class="text-center text-muted py-3">No se detectaron patentes o marcas comerciales registradas ante el IMPI para este titular.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Registro</th>
                                                <th>Expediente</th>
                                                <th>Denominación</th>
                                                <th>Titular</th>
                                                <th>Clase Nice</th>
                                                <th>Concesión</th>
                                                <th>Estatus</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $marcas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $marca): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td><code><?php echo e($marca['numero_registro'] ?? 'N/A'); ?></code></td>
                                                <td><code><?php echo e($marca['numero_expediente'] ?? 'N/A'); ?></code></td>
                                                <td class="fw-semibold text-dark"><?php echo e($marca['denominacion'] ?? ''); ?></td>
                                                <td><?php echo e($marca['titular'] ?? ''); ?></td>
                                                <td><span class="badge bg-light text-dark">Clase <?php echo e($marca['clase_nice'] ?? ''); ?></span></td>
                                                <td><?php echo e(isset($marca['fecha_concesion']) ? \Carbon\Carbon::parse($marca['fecha_concesion'])->format('d/m/Y') : 'N/A'); ?></td>
                                                <td><span class="badge bg-success-subtle text-success"><?php echo e($marca['estatus'] ?? 'REGISTRADA'); ?></span></td>
                                            </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php if($isProcessing): ?>
<?php $__env->startSection('script'); ?>
<script>
    setTimeout(function() {
        window.location.reload();
    }, 3000);
</script>
<?php $__env->stopSection(); ?>
<?php endif; ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Atlas\resources\views/tenant/subjects/show.blade.php ENDPATH**/ ?>