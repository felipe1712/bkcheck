<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Due Diligence: <?php echo e($subject->name_or_company); ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #405189;
            padding-bottom: 10px;
        }
        .logo-text {
            font-size: 20px;
            font-weight: bold;
            color: #405189;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            color: #555555;
            text-transform: uppercase;
        }
        .metadata-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .metadata-table td {
            padding: 4px 0;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            background-color: #f3f6f9;
            color: #405189;
            padding: 6px 10px;
            margin-top: 20px;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-left: 3px solid #405189;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table th {
            background-color: #f3f6f9;
            color: #333333;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #e9ebec;
        }
        .data-table td {
            padding: 6px 8px;
            border: 1px solid #e9ebec;
            vertical-align: top;
        }
        .badge {
            display: inline-block;
            padding: 3px 6px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-success {
            background-color: #daf4f0;
            color: #0ab39c;
        }
        .badge-danger {
            background-color: #fde8e4;
            color: #f06548;
        }
        .badge-warning {
            background-color: #fef4e4;
            color: #f7b84b;
        }
        .badge-info {
            background-color: #e1f5fe;
            color: #03a9f4;
        }
        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            height: 30px;
            text-align: center;
            font-size: 8px;
            color: #888888;
            border-top: 1px solid #e9ebec;
            padding-top: 5px;
        }
        .page-break {
            page-break-after: always;
        }
        .disclaimer-box {
            border: 1px solid #f7b84b;
            background-color: #fffbeb;
            padding: 10px;
            margin-top: 30px;
            border-radius: 4px;
        }
        .disclaimer-title {
            font-weight: bold;
            color: #b25e00;
            margin-bottom: 5px;
        }
        .disclaimer-text {
            color: #666;
            font-size: 9px;
            text-align: justify;
        }
        code {
            font-family: Courier, monospace;
            background-color: #f1f1f1;
            padding: 2px 4px;
            border-radius: 3px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table style="width: 100%;" class="header">
        <tr>
            <td>
                <span class="logo-text">ATLAS</span><br>
                <span style="color:#777; font-size:9px;">PLATAFORMA DE DUE DILIGENCE</span>
            </td>
            <td style="text-align: right;">
                <span class="report-title">Expediente de Validación</span><br>
                <span style="color:#777; font-size:9px;">Fecha de Generación: <?php echo e(now()->format('d/m/Y H:i')); ?></span>
            </td>
        </tr>
    </table>

    <!-- Subject Metadata -->
    <div class="section-title">Información General del Sujeto</div>
    <table class="metadata-table">
        <tr>
            <td style="width: 20%; font-weight: bold;">Nombre / Razón Social:</td>
            <td style="width: 30%;"><?php echo e($subject->name_or_company); ?></td>
            <td style="width: 20%; font-weight: bold;">RFC:</td>
            <td style="width: 30%;"><code><?php echo e($subject->rfc); ?></code></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Tipo de Persona:</td>
            <td><?php echo e($subject->tipo === 'persona_fisica' ? 'Persona Física' : 'Persona Moral'); ?></td>
            <td style="font-weight: bold;">CURP:</td>
            <td><code><?php echo e($subject->curp ?: 'N/A'); ?></code></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Domicilio:</td>
            <td colspan="3"><?php echo e($subject->address ?: 'No provisto'); ?></td>
        </tr>
    </table>

    <div class="section-title">Consentimiento Legal y Cumplimiento</div>
    <table class="metadata-table">
        <tr>
            <td style="width: 20%; font-weight: bold;">Estado de Carta:</td>
            <td style="width: 30%;">
                <span class="badge badge-success">Otorgado</span>
            </td>
            <td style="width: 20%; font-weight: bold;">Fecha de Firma:</td>
            <td style="width: 30%;"><?php echo e($subject->consent_date ? $subject->consent_date->format('d/m/Y H:i') : 'N/A'); ?></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Finalidad / Base Legal:</td>
            <td colspan="3"><?php echo e($subject->consent_legal_basis); ?></td>
        </tr>
    </table>

    <div class="section-title">Resumen de Resultados por Fuentes</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40%;">Fuente de Información</th>
                <th style="width: 25%;">Estatus Consulta</th>
                <th style="width: 35%;">Resultado General</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $queries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $query): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td style="font-weight: bold;">
                    <?php switch($query->source_type):
                        case ('rfc'): ?> Validación RFC (SAT) <?php break; ?>
                        <?php case ('csd'): ?> Certificados CSD y e-Firma (SAT) <?php break; ?>
                        <?php case ('siger'): ?> Registro Público de Comercio (SIGER) <?php break; ?>
                        <?php case ('sat_listas'): ?> Listas 69 / 69-B (SAT) <?php break; ?>
                        <?php case ('marcas'): ?> Búsqueda de Marcas (IMPI) <?php break; ?>
                        <?php default: ?> <?php echo e($query->source_type); ?>

                    <?php endswitch; ?>
                </td>
                <td>
                    <span class="badge badge-success">Completado</span>
                </td>
                <td>
                    <?php if($query->source_type === 'rfc'): ?>
                        <?php if($query->result && isset($query->result->processed_data['valido']) && $query->result->processed_data['valido']): ?>
                            <span class="badge badge-success">RFC Válido</span> (<?php echo e($query->result->processed_data['situacion'] ?? 'ACTIVO'); ?>)
                        <?php else: ?>
                            <span class="badge badge-danger">RFC Inválido</span>
                        <?php endif; ?>
                    <?php elseif($query->source_type === 'csd'): ?>
                        <?php echo e(count($query->result->processed_data['certificados'] ?? [])); ?> Certificados detectados
                    <?php elseif($query->source_type === 'siger'): ?>
                        <?php echo e(count($query->result->processed_data['resultados'] ?? [])); ?> Registro(s) mercantil(es)
                    <?php elseif($query->source_type === 'sat_listas'): ?>
                        <?php if($query->result && isset($query->result->processed_data['en_lista_69b']) && $query->result->processed_data['en_lista_69b']): ?>
                            <span class="badge badge-danger">ALERTA: Boletinado 69-B</span>
                        <?php else: ?>
                            <span class="badge badge-success">Sin Incidencias</span>
                        <?php endif; ?>
                    <?php elseif($query->source_type === 'marcas'): ?>
                        <?php echo e(count($query->result->processed_data['marcas'] ?? [])); ?> Marca(s) encontrada(s)
                    <?php else: ?>
                        Consulta realizada
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- Detailed Query Sections -->
    <?php $__currentLoopData = $queries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $query): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="section-title">
            Detalle: 
            <?php switch($query->source_type):
                case ('rfc'): ?> Validación RFC (SAT) <?php break; ?>
                <?php case ('csd'): ?> Certificados CSD y e-Firma (SAT) <?php break; ?>
                <?php case ('siger'): ?> Registro Público de Comercio (SIGER) <?php break; ?>
                <?php case ('sat_listas'): ?> Listas 69 / 69-B (SAT) <?php break; ?>
                <?php case ('marcas'): ?> Búsqueda de Marcas (IMPI) <?php break; ?>
                <?php default: ?> <?php echo e($query->source_type); ?>

            <?php endswitch; ?>
        </div>

        <?php if($query->source_type === 'rfc' && $query->result): ?>
            <?php $rfcData = $query->result->processed_data; ?>
            <table class="metadata-table">
                <tr>
                    <td style="width: 25%; font-weight: bold;">RFC Consultado:</td>
                    <td style="width: 75%;"><code><?php echo e($rfcData['rfc'] ?? ''); ?></code></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Estatus SAT:</td>
                    <td>
                        <span class="badge <?php echo e(($rfcData['situacion'] ?? 'ACTIVO') === 'ACTIVO' ? 'badge-success' : 'badge-warning'); ?>">
                            <?php echo e($rfcData['situacion'] ?? 'INACTIVO'); ?>

                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Razón Social / Denominación:</td>
                    <td><?php echo e($rfcData['razon_social'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Tipo de Persona:</td>
                    <td><?php echo e($rfcData['tipo_persona'] ?? 'N/A'); ?></td>
                </tr>
                <?php if(isset($rfcData['curp']) && $rfcData['curp']): ?>
                <tr>
                    <td style="font-weight: bold;">CURP Asociado:</td>
                    <td><code><?php echo e($rfcData['curp']); ?></code></td>
                </tr>
                <?php endif; ?>
            </table>

        <?php elseif($query->source_type === 'csd' && $query->result): ?>
            <?php $certs = $query->result->processed_data['certificados'] ?? []; ?>
            <?php if(empty($certs)): ?>
                <p>No se encontraron certificados de sellos digitales registrados ante el SAT para este RFC.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Número de Serie</th>
                            <th>Tipo</th>
                            <th>Estatus</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $certs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><code><?php echo e($cert['numero_serie'] ?? ''); ?></code></td>
                            <td><?php echo e($cert['tipo'] ?? 'CSD'); ?></td>
                            <td>
                                <span class="badge <?php echo e(($cert['estado'] ?? 'ACTIVO') === 'ACTIVO' ? 'badge-success' : 'badge-danger'); ?>">
                                    <?php echo e($cert['estado'] ?? 'CADUCO'); ?>

                                </span>
                            </td>
                            <td><?php echo e(isset($cert['fecha_inicio']) ? \Carbon\Carbon::parse($cert['fecha_inicio'])->format('d/m/Y H:i') : ''); ?></td>
                            <td><?php echo e(isset($cert['fecha_fin']) ? \Carbon\Carbon::parse($cert['fecha_fin'])->format('d/m/Y H:i') : ''); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php elseif($query->source_type === 'siger' && $query->result): ?>
            <?php $results = $query->result->processed_data['resultados'] ?? []; ?>
            <?php if(empty($results)): ?>
                <p>No se encontraron registros de actas mercantiles o constitución de sociedades en el SIGER para este sujeto.</p>
            <?php else: ?>
                <?php $__currentLoopData = $results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $res): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <table class="metadata-table">
                    <tr>
                        <td style="width: 25%; font-weight: bold;">Folio Mercantil (FME):</td>
                        <td style="width: 25%;"><?php echo e($res['fme'] ?? ''); ?></td>
                        <td style="width: 25%; font-weight: bold;">Entidad Federativa:</td>
                        <td style="width: 25%;"><?php echo e($res['entidad_federativa'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Fecha Constitución:</td>
                        <td><?php echo e(isset($res['fecha_constitucion']) ? \Carbon\Carbon::parse($res['fecha_constitucion'])->format('d/m/Y') : ''); ?></td>
                        <td style="font-weight: bold;">Capital Social:</td>
                        <td>$<?php echo e(number_format($res['capital_social'] ?? 0, 2)); ?> MXN</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Objeto Social:</td>
                        <td colspan="3" style="text-align: justify;"><?php echo e($res['objeto_social'] ?? 'N/A'); ?></td>
                    </tr>
                </table>

                <h4 style="font-size: 10px; text-transform: uppercase; margin-bottom: 5px; color:#555;">Composición de Socios / Accionistas</h4>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre del Socio / Accionista</th>
                            <th>Porcentaje de Participación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $res['socios'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $socio): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($socio['nombre'] ?? ''); ?></td>
                            <td style="font-weight: bold;"><?php echo e($socio['participacion'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>

        <?php elseif($query->source_type === 'sat_listas' && $query->result): ?>
            <?php $listData = $query->result->processed_data; ?>
            <table class="metadata-table">
                <tr>
                    <td style="width: 30%; font-weight: bold;">Lista 69 (Exceptuados):</td>
                    <td style="width: 70%;">
                        <?php if($listData['en_lista_69'] ?? false): ?>
                            <span class="badge badge-danger">Encontrado en Lista 69</span>
                        <?php else: ?>
                            <span class="badge badge-success">Sin Incidencias</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Lista 69-B (Facturación Simulada):</td>
                    <td>
                        <?php if($listData['en_lista_69b'] ?? false): ?>
                            <span class="badge badge-danger">Alerta: Contribuyente Boletinado 69-B</span>
                        <?php else: ?>
                            <span class="badge badge-success">Sin Incidencias (No EFOS/EDOS)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if($listData['en_lista_69b'] ?? false): ?>
                <tr>
                    <td style="font-weight: bold;">Estatus 69-B:</td>
                    <td><span class="badge badge-warning"><?php echo e($listData['estatus_69b'] ?? 'Presunto'); ?></span></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Número de Oficio SAT:</td>
                    <td><code><?php echo e($listData['oficio_oficial'] ?? 'N/A'); ?></code></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Fecha de Publicación DOF:</td>
                    <td><?php echo e($listData['fecha_publicacion'] ?? 'N/A'); ?></td>
                </tr>
                <?php endif; ?>
            </table>

        <?php elseif($query->source_type === 'marcas' && $query->result): ?>
            <?php $marcas = $query->result->processed_data['marcas'] ?? []; ?>
            <?php if(empty($marcas)): ?>
                <p>No se encontraron registros de marcas registradas o solicitudes vigentes ante el IMPI para este sujeto.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
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
                            <td style="font-weight: bold;"><?php echo e($marca['denominacion'] ?? ''); ?></td>
                            <td><?php echo e($marca['titular'] ?? ''); ?></td>
                            <td><?php echo e($marca['clase_nice'] ?? ''); ?></td>
                            <td><?php echo e(isset($marca['fecha_concesion']) ? \Carbon\Carbon::parse($marca['fecha_concesion'])->format('d/m/Y') : 'N/A'); ?></td>
                            <td>
                                <span class="badge badge-success"><?php echo e($marca['estatus'] ?? 'REGISTRADA'); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
        <hr style="border: 0; border-top: 1px dashed #e9ebec; margin: 15px 0;">
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <!-- Legal Disclaimer Section -->
    <div class="disclaimer-box">
        <div class="disclaimer-title">DESCARGO DE RESPONSABILIDAD LEGAL (DISCLAIMER)</div>
        <div class="disclaimer-text">
            Este reporte contiene información consolidada obtenida de bases de datos públicas y registros gubernamentales al amparo de las leyes mexicanas aplicables. La validez de los datos corresponde únicamente al momento de realizar la consulta en los portales correspondientes del Servicio de Administración Tributaria (SAT), de la Secretaría de Economía (SIGER) y del Instituto Mexicano de la Propiedad Industrial (IMPI). La plataforma no se responsabiliza de posibles imprecisiones, actualizaciones tardías, suspensiones de portales o cambios retrospectivos hechos por dichas instituciones. Toda la información personal sensible contenida en este expediente ha sido tratada en estricto cumplimiento con la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP), con previa obtención del consentimiento firmado y debidamente registrado del sujeto titular. Este reporte es para uso interno y confidencial del cliente de due diligence y no constituye una opinión legal vinculante ni una calificación definitiva de solvencia moral o mercantil.
        </div>
    </div>

    <!-- Footer for page numbers and brand -->
    <div class="footer">
        Atlas Due Diligence — Reporte Confidencial Generado bajo Licencia. Página 1 de 1.
    </div>

</body>
</html>
<?php /**PATH C:\laragon\www\Atlas\resources\views/tenant/reports/subject_pdf.blade.php ENDPATH**/ ?>