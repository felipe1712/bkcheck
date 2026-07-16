<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Due Diligence: {{ $subject->name_or_company }}</title>
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
                <span style="color:#777; font-size:9px;">Fecha de Generación: {{ now()->format('d/m/Y H:i') }}</span>
            </td>
        </tr>
    </table>

    <!-- Subject Metadata -->
    <div class="section-title">Información General del Sujeto</div>
    <table class="metadata-table">
        <tr>
            <td style="width: 20%; font-weight: bold;">Nombre / Razón Social:</td>
            <td style="width: 30%;">{{ $subject->name_or_company }}</td>
            <td style="width: 20%; font-weight: bold;">RFC:</td>
            <td style="width: 30%;"><code>{{ $subject->rfc }}</code></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Tipo de Persona:</td>
            <td>{{ $subject->tipo === 'persona_fisica' ? 'Persona Física' : 'Persona Moral' }}</td>
            <td style="font-weight: bold;">CURP:</td>
            <td><code>{{ $subject->curp ?: 'N/A' }}</code></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Domicilio:</td>
            <td colspan="3">{{ $subject->address ?: 'No provisto' }}</td>
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
            <td style="width: 30%;">{{ $subject->consent_date ? $subject->consent_date->format('d/m/Y H:i') : 'N/A' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Finalidad / Base Legal:</td>
            <td colspan="3">{{ $subject->consent_legal_basis }}</td>
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
            @foreach($queries as $query)
            <tr>
                <td style="font-weight: bold;">
                    @switch($query->source_type)
                        @case('rfc') Validación RFC (SAT) @break
                        @case('csd') Certificados CSD y e-Firma (SAT) @break
                        @case('siger') Registro Público de Comercio (SIGER) @break
                        @case('sat_listas') Listas 69 / 69-B (SAT) @break
                        @case('marcas') Búsqueda de Marcas (IMPI) @break
                        @case('ine_frente') Identificación INE Frente (OCR) @break
                        @case('ine_reverso') Identificación INE Reverso (OCR) @break
                        @default {{ $query->source_type }}
                    @endswitch
                </td>
                <td>
                    <span class="badge badge-success">Completado</span>
                </td>
                <td>
                    @if($query->source_type === 'rfc')
                        @if($query->result && isset($query->result->processed_data['valido']) && $query->result->processed_data['valido'])
                            <span class="badge badge-success">RFC Válido</span> ({{ $query->result->processed_data['situacion'] ?? 'ACTIVO' }})
                        @else
                            <span class="badge badge-danger">RFC Inválido</span>
                        @endif
                    @elseif($query->source_type === 'csd')
                        {{ count($query->result->processed_data['certificados'] ?? []) }} Certificados detectados
                    @elseif($query->source_type === 'siger')
                        {{ count($query->result->processed_data['resultados'] ?? []) }} Registro(s) mercantil(es)
                    @elseif($query->source_type === 'sat_listas')
                        @if($query->result && isset($query->result->processed_data['en_lista_69b']) && $query->result->processed_data['en_lista_69b'])
                            <span class="badge badge-danger">ALERTA: Boletinado 69-B</span>
                        @else
                            <span class="badge badge-success">Sin Incidencias</span>
                        @endif
                    @elseif($query->source_type === 'marcas')
                        {{ count($query->result->processed_data['marcas'] ?? []) }} Marca(s) encontrada(s)
                    @elseif($query->source_type === 'ine_frente')
                        Lectura OCR frontal completada
                    @elseif($query->source_type === 'ine_reverso')
                        Lectura OCR reverso completada
                    @else
                        Consulta realizada
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- Detailed Query Sections -->
    @foreach($queries as $query)
        <div class="section-title">
            Detalle: 
            @switch($query->source_type)
                @case('rfc') Validación RFC (SAT) @break
                @case('csd') Certificados CSD y e-Firma (SAT) @break
                @case('siger') Registro Público de Comercio (SIGER) @break
                @case('sat_listas') Listas 69 / 69-B (SAT) @break
                @case('marcas') Búsqueda de Marcas (IMPI) @break
                @case('ine_frente') Identificación INE Frente (OCR) @break
                @case('ine_reverso') Identificación INE Reverso (OCR) @break
                @default {{ $query->source_type }}
            @endswitch
        </div>

        @if($query->source_type === 'rfc' && $query->result)
            @php $rfcData = $query->result->processed_data; @endphp
            <table class="metadata-table">
                <tr>
                    <td style="width: 25%; font-weight: bold;">RFC Consultado:</td>
                    <td style="width: 75%;"><code>{{ $rfcData['rfc'] ?? '' }}</code></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Estatus SAT:</td>
                    <td>
                        <span class="badge {{ ($rfcData['situacion'] ?? 'ACTIVO') === 'ACTIVO' ? 'badge-success' : 'badge-warning' }}">
                            {{ $rfcData['situacion'] ?? 'INACTIVO' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Razón Social / Denominación:</td>
                    <td>{{ $rfcData['razon_social'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Tipo de Persona:</td>
                    <td>{{ $rfcData['tipo_persona'] ?? 'N/A' }}</td>
                </tr>
                @if(isset($rfcData['curp']) && $rfcData['curp'])
                <tr>
                    <td style="font-weight: bold;">CURP Asociado:</td>
                    <td><code>{{ $rfcData['curp'] }}</code></td>
                </tr>
                @endif
            </table>

        @elseif($query->source_type === 'csd' && $query->result)
            @php $certs = $query->result->processed_data['certificados'] ?? []; @endphp
            @if(empty($certs))
                <p>No se encontraron certificados de sellos digitales registrados ante el SAT para este RFC.</p>
            @else
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
                        @foreach($certs as $cert)
                        <tr>
                            <td><code>{{ $cert['numero_serie'] ?? '' }}</code></td>
                            <td>{{ $cert['tipo'] ?? 'CSD' }}</td>
                            <td>
                                <span class="badge {{ ($cert['estado'] ?? 'ACTIVO') === 'ACTIVO' ? 'badge-success' : 'badge-danger' }}">
                                    {{ $cert['estado'] ?? 'CADUCO' }}
                                </span>
                            </td>
                            <td>{{ isset($cert['fecha_inicio']) ? \Carbon\Carbon::parse($cert['fecha_inicio'])->format('d/m/Y H:i') : '' }}</td>
                            <td>{{ isset($cert['fecha_fin']) ? \Carbon\Carbon::parse($cert['fecha_fin'])->format('d/m/Y H:i') : '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

        @elseif($query->source_type === 'siger' && $query->result)
            @php $results = $query->result->processed_data['resultados'] ?? []; @endphp
            @if(empty($results))
                <p>No se encontraron registros de actas mercantiles o constitución de sociedades en el SIGER para este sujeto.</p>
            @else
                @foreach($results as $res)
                <table class="metadata-table">
                    <tr>
                        <td style="width: 25%; font-weight: bold;">Folio Mercantil (FME):</td>
                        <td style="width: 25%;">{{ $res['fme'] ?? '' }}</td>
                        <td style="width: 25%; font-weight: bold;">Entidad Federativa:</td>
                        <td style="width: 25%;">{{ $res['entidad_federativa'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Fecha Constitución:</td>
                        <td>{{ isset($res['fecha_constitucion']) ? \Carbon\Carbon::parse($res['fecha_constitucion'])->format('d/m/Y') : '' }}</td>
                        <td style="font-weight: bold;">Capital Social:</td>
                        <td>${{ number_format($res['capital_social'] ?? 0, 2) }} MXN</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Objeto Social:</td>
                        <td colspan="3" style="text-align: justify;">{{ $res['objeto_social'] ?? 'N/A' }}</td>
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
                        @foreach($res['socios'] ?? [] as $socio)
                        <tr>
                            <td>{{ $socio['nombre'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $socio['participacion'] ?? '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endforeach
            @endif

        @elseif($query->source_type === 'sat_listas' && $query->result)
            @php $listData = $query->result->processed_data; @endphp
            <table class="metadata-table">
                <tr>
                    <td style="width: 30%; font-weight: bold;">Lista 69 (Exceptuados):</td>
                    <td style="width: 70%;">
                        @if($listData['en_lista_69'] ?? false)
                            <span class="badge badge-danger">Encontrado en Lista 69</span>
                        @else
                            <span class="badge badge-success">Sin Incidencias</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Lista 69-B (Facturación Simulada):</td>
                    <td>
                        @if($listData['en_lista_69b'] ?? false)
                            <span class="badge badge-danger">Alerta: Contribuyente Boletinado 69-B</span>
                        @else
                            <span class="badge badge-success">Sin Incidencias (No EFOS/EDOS)</span>
                        @endif
                    </td>
                </tr>
                @if($listData['en_lista_69b'] ?? false)
                <tr>
                    <td style="font-weight: bold;">Estatus 69-B:</td>
                    <td><span class="badge badge-warning">{{ $listData['estatus_69b'] ?? 'Presunto' }}</span></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Número de Oficio SAT:</td>
                    <td><code>{{ $listData['oficio_oficial'] ?? 'N/A' }}</code></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Fecha de Publicación DOF:</td>
                    <td>{{ $listData['fecha_publicacion'] ?? 'N/A' }}</td>
                </tr>
                @endif
            </table>

        @elseif($query->source_type === 'marcas' && $query->result)
            @php $marcas = $query->result->processed_data['marcas'] ?? []; @endphp
            @if(empty($marcas))
                <p>No se encontraron registros de marcas registradas o solicitudes vigentes ante el IMPI para este sujeto.</p>
            @else
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
                        @foreach($marcas as $marca)
                        <tr>
                            <td><code>{{ $marca['numero_registro'] ?? 'N/A' }}</code></td>
                            <td><code>{{ $marca['numero_expediente'] ?? 'N/A' }}</code></td>
                            <td style="font-weight: bold;">{{ $marca['denominacion'] ?? '' }}</td>
                            <td>{{ $marca['titular'] ?? '' }}</td>
                            <td>{{ $marca['clase_nice'] ?? '' }}</td>
                            <td>{{ isset($marca['fecha_concesion']) ? \Carbon\Carbon::parse($marca['fecha_concesion'])->format('d/m/Y') : 'N/A' }}</td>
                            <td>
                                <span class="badge badge-success">{{ $marca['estatus'] ?? 'REGISTRADA' }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @elseif($query->source_type === 'ine_frente' && $query->result)
            @php $frenteData = $query->result->processed_data; @endphp
            <table class="metadata-table">
                <tr>
                    <td style="width: 25%; font-weight: bold;">Nombre Completo:</td>
                    <td style="width: 75%;">{{ ($frenteData['nombre'] ?? '') . ' ' . ($frenteData['apellido_paterno'] ?? '') . ' ' . ($frenteData['apellido_materno'] ?? '') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">CURP:</td>
                    <td><code>{{ $frenteData['curp'] ?? 'N/A' }}</code></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Clave Elector:</td>
                    <td><code>{{ $frenteData['clave_elector'] ?? 'N/A' }}</code></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Sección Electoral:</td>
                    <td>{{ $frenteData['seccion'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Vigencia:</td>
                    <td>{{ $frenteData['vigencia'] ?? 'N/A' }}</td>
                </tr>
            </table>

        @elseif($query->source_type === 'ine_reverso' && $query->result)
            @php $reversoData = $query->result->processed_data; @endphp
            <table class="metadata-table">
                <tr>
                    <td style="width: 25%; font-weight: bold;">Código CIC:</td>
                    <td style="width: 75%;"><code>{{ $reversoData['cic'] ?? 'N/A' }}</code></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Código OCR:</td>
                    <td><code>{{ $reversoData['codigo_ocr'] ?? 'N/A' }}</code></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Número Identificador:</td>
                    <td><code>{{ $reversoData['numero_identificador'] ?? 'N/A' }}</code></td>
                </tr>
            </table>
        @elseif($query->source_type === 'sanciones' && $query->result)
            @php $sancData = $query->result->processed_data; @endphp
            @if(empty($sancData['hits'] ?? []))
                <p>Sin incidencias registradas en listas de sanciones, PEPs o terrorismo.</p>
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Lista</th>
                            <th>Nombre Detectado</th>
                            <th>País</th>
                            <th>Tipo</th>
                            <th>Comentarios</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sancData['hits'] as $hit)
                        <tr>
                            <td style="font-weight: bold; color: #f06548;">{{ $hit['lista'] ?? 'N/A' }}</td>
                            <td>{{ $hit['nombre_encontrado'] ?? 'N/A' }}</td>
                            <td>{{ $hit['entidad_pais'] ?? 'N/A' }}</td>
                            <td>{{ $hit['tipo_lista'] ?? 'Sanción' }}</td>
                            <td>{{ $hit['comentarios'] ?? '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

        @elseif($query->source_type === 'litigios' && $query->result)
            @php $litigData = $query->result->processed_data; @endphp
            @if(empty($litigData['juicios'] ?? []))
                <p>Sin historial de juicios o demandas registrado ante juzgados locales o federales.</p>
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Expediente</th>
                            <th>Juzgado</th>
                            <th>Materia</th>
                            <th>Actor</th>
                            <th>Demandado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($litigData['juicios'] as $juicio)
                        <tr>
                            <td><code>{{ $juicio['expediente'] ?? 'N/A' }}</code></td>
                            <td>{{ $juicio['juzgado'] ?? 'N/A' }}</td>
                            <td>{{ $juicio['materia'] ?? 'Civil' }}</td>
                            <td>{{ $juicio['actor'] ?? 'N/A' }}</td>
                            <td>{{ $juicio['demandado'] ?? 'N/A' }}</td>
                            <td>{{ isset($juicio['fecha']) ? \Carbon\Carbon::parse($juicio['fecha'])->format('d/m/Y') : 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif
        <hr style="border: 0; border-top: 1px dashed #e9ebec; margin: 15px 0;">
    @endforeach

    {{-- ══════════ SECCIONES TIER 2 ══════════ --}}
    @php
        $curpQ    = $queries->firstWhere('source_type', 'curp');
        $domQ     = $queries->firstWhere('source_type', 'comprobante_domicilio');
        $nssQ     = $queries->firstWhere('source_type', 'nss_imss');
        $scoreQ   = $queries->firstWhere('source_type', 'score_crediticio');
        $denueQ   = $queries->firstWhere('source_type', 'denue');
        $curpPdf  = $curpQ?->result?->processed_data  ?? [];
        $domPdf   = $domQ?->result?->processed_data   ?? [];
        $nssPdf   = $nssQ?->result?->processed_data   ?? [];
        $scorePdf = $scoreQ?->result?->processed_data ?? [];
        $denuePdf = $denueQ?->result?->processed_data ?? [];
    @endphp

    {{-- CURP / RENAPO --}}
    @if($curpQ && $curpQ->status === 'completed' && !empty($curpPdf))
    <div class="section-title">Validación CURP / RENAPO</div>
    <table class="data-table">
        <tbody>
            <tr><td style="width:30%;font-weight:bold;">CURP</td><td><code>{{ $curpPdf['curp'] ?? $subject->curp }}</code></td><td style="width:30%;font-weight:bold;">Estatus RENAPO</td><td>{{ $curpPdf['estatus_curp'] ?? '—' }}</td></tr>
            <tr><td style="font-weight:bold;">Nombre registrado</td><td>{{ trim(($curpPdf['nombre'] ?? '') . ' ' . ($curpPdf['primer_apellido'] ?? '') . ' ' . ($curpPdf['segundo_apellido'] ?? '')) ?: '—' }}</td><td style="font-weight:bold;">Fecha de nacimiento</td><td>{{ $curpPdf['fecha_nacimiento'] ?? '—' }}</td></tr>
            <tr><td style="font-weight:bold;">Sexo</td><td>{{ $curpPdf['sexo'] ?? '—' }}</td><td style="font-weight:bold;">Entidad de nacimiento</td><td>{{ $curpPdf['estado_nacimiento'] ?? '—' }}</td></tr>
            <tr><td style="font-weight:bold;">Válida</td><td colspan="3"><strong style="color:{{ ($curpPdf['valida'] ?? false) ? '#0ab39c' : '#f06548' }};">{{ ($curpPdf['valida'] ?? false) ? '✓ CURP válida ante RENAPO' : '✗ CURP no válida o no encontrada' }}</strong></td></tr>
        </tbody>
    </table>
    <hr style="border: 0; border-top: 1px dashed #e9ebec; margin: 15px 0;">
    @endif

    {{-- Comprobante de Domicilio --}}
    @if($domQ && $domQ->status === 'completed' && !empty($domPdf))
    <div class="section-title">Comprobante de Domicilio — OCR</div>
    <table class="data-table">
        <tbody>
            <tr><td style="width:30%;font-weight:bold;">Tipo</td><td>{{ $domPdf['tipo_comprobante'] ?? '—' }}</td><td style="width:30%;font-weight:bold;">Titular</td><td>{{ $domPdf['titular'] ?? '—' }}</td></tr>
            <tr><td style="font-weight:bold;">Domicilio extraído</td><td colspan="3">{{ collect([$domPdf['calle'] ?? null, $domPdf['num_exterior'] ?? null, $domPdf['colonia'] ?? null, $domPdf['municipio'] ?? null, $domPdf['estado'] ?? null])->filter()->implode(', ') ?: '—' }}{{ !empty($domPdf['codigo_postal']) ? ' · C.P. ' . $domPdf['codigo_postal'] : '' }}</td></tr>
            <tr><td style="font-weight:bold;">Fecha / Período</td><td>{{ $domPdf['periodo_facturado'] ?? $domPdf['fecha_emision'] ?? '—' }}</td><td style="font-weight:bold;">Coincide con sujeto</td><td><strong style="color:{{ ($domPdf['coincide_con_sujeto'] ?? false) ? '#0ab39c' : '#f06548' }};">{{ ($domPdf['coincide_con_sujeto'] ?? false) ? '✓ Sí' : '✗ No' }}</strong></td></tr>
        </tbody>
    </table>
    <hr style="border: 0; border-top: 1px dashed #e9ebec; margin: 15px 0;">
    @endif

    {{-- NSS / IMSS --}}
    @if($nssQ && $nssQ->status === 'completed' && !empty($nssPdf))
    <div class="section-title">Historial Laboral IMSS (NSS)</div>
    <table class="data-table">
        <tbody>
            <tr><td style="width:30%;font-weight:bold;">NSS</td><td><code>{{ $nssPdf['nss'] ?? '—' }}</code></td><td style="width:30%;font-weight:bold;">Semanas cotizadas</td><td><strong>{{ $nssPdf['semanas_cotizadas'] ?? '—' }}</strong></td></tr>
            <tr><td style="font-weight:bold;">Último patrón</td><td>{{ $nssPdf['ultimo_patron'] ?? '—' }}</td><td style="font-weight:bold;">Últ. cotización</td><td>{{ $nssPdf['fecha_ultima_cotizacion'] ?? '—' }}</td></tr>
            <tr><td style="font-weight:bold;">Estatus IMSS</td><td>{{ ($nssPdf['activo_actualmente'] ?? false) ? 'Activo' : 'Baja' }}</td><td style="font-weight:bold;">SBC</td><td>${{ number_format($nssPdf['salario_base_cotizacion'] ?? 0, 2) }}/día</td></tr>
        </tbody>
    </table>
    @if(!empty($nssPdf['historial_empleos']))
    <table class="data-table" style="margin-top:6px;">
        <thead><tr><th>Patrón</th><th>Tipo</th><th>Inicio</th><th>Baja</th><th>Semanas</th></tr></thead>
        <tbody>
            @foreach(array_slice($nssPdf['historial_empleos'], 0, 5) as $emp)
            <tr>
                <td>{{ $emp['patron'] ?? '—' }}</td>
                <td>{{ $emp['tipo_movimiento'] ?? '—' }}</td>
                <td>{{ $emp['fecha_inicio'] ?? '—' }}</td>
                <td>{{ $emp['fecha_baja'] ?? 'Vigente' }}</td>
                <td>{{ $emp['semanas'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    <hr style="border: 0; border-top: 1px dashed #e9ebec; margin: 15px 0;">
    @endif

    {{-- Score Crediticio --}}
    @if($scoreQ && $scoreQ->status === 'completed' && !empty($scorePdf))
    <div class="section-title">Score Crediticio — Buró de Crédito</div>
    <table class="data-table">
        <tbody>
            <tr><td style="width:30%;font-weight:bold;">Score Buró</td><td><strong style="font-size:16px;">{{ $scorePdf['score_buro'] ?? '—' }}</strong></td><td style="width:30%;font-weight:bold;">Rango</td><td>{{ $scorePdf['rango_score'] ?? '—' }}</td></tr>
            <tr><td style="font-weight:bold;">Nivel de riesgo</td><td>{{ $scorePdf['nivel_riesgo'] ?? '—' }}</td><td style="font-weight:bold;">Cuentas activas</td><td>{{ $scorePdf['cuentas_activas'] ?? '—' }}</td></tr>
            <tr><td style="font-weight:bold;">Cuentas en mora</td><td style="color:{{ ($scorePdf['cuentas_en_mora'] ?? 0) > 0 ? '#f06548' : '#0ab39c' }};"><strong>{{ $scorePdf['cuentas_en_mora'] ?? 0 }}</strong></td><td style="font-weight:bold;">Monto vencido</td><td>${{ number_format($scorePdf['monto_vencido'] ?? 0, 2) }}</td></tr>
            <tr><td style="font-weight:bold;">Deuda total</td><td>${{ number_format($scorePdf['monto_total_deuda'] ?? 0, 2) }}</td><td style="font-weight:bold;">Consultas recientes</td><td>{{ $scorePdf['consultas_recientes'] ?? '—' }}</td></tr>
        </tbody>
    </table>
    @if(!empty($scorePdf['aviso_legal']))
    <p style="font-size:10px;color:#666;margin-top:6px;"><em>⚠ {{ $scorePdf['aviso_legal'] }}</em></p>
    @endif
    <hr style="border: 0; border-top: 1px dashed #e9ebec; margin: 15px 0;">
    @endif

    {{-- DENUE --}}
    @if($denueQ && $denueQ->status === 'completed' && !empty($denuePdf['establecimientos'] ?? []))
    <div class="section-title">Directorio Empresarial DENUE — INEGI</div>
    <p style="font-size:11px;color:#666;margin-bottom:8px;">
        Se encontraron <strong>{{ $denuePdf['total_encontrados'] ?? 0 }}</strong> registro(s) en el Directorio Estadístico Nacional de Unidades Económicas del INEGI.
    </p>
    <table class="data-table">
        <thead><tr><th>Nombre / Razón Social</th><th>Actividad (SCIAN)</th><th>Personal</th><th>Domicilio</th></tr></thead>
        <tbody>
            @foreach($denuePdf['establecimientos'] as $e)
            <tr>
                <td>
                    <strong>{{ $e['nombre_estab'] ?? '—' }}</strong>
                    @if(!empty($e['id_denue']))<br><small>ID: {{ $e['id_denue'] }}</small>@endif
                </td>
                <td>{{ $e['codigo_act'] ?? '' }} — {{ $e['actividad'] ?? '—' }}</td>
                <td>{{ $e['personal_ocupado'] ?? '—' }}</td>
                <td>
                    {{ collect([$e['calle'] ?? null, $e['num_exterior'] ?? null, $e['colonia'] ?? null, $e['municipio'] ?? null, $e['entidad'] ?? null])->filter()->implode(', ') ?: '—' }}
                    @if(!empty($e['codigo_postal'])) · C.P. {{ $e['codigo_postal'] }} @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p style="font-size:10px;color:#666;margin-top:4px;"><em>Fuente: INEGI DENUE — Datos Abiertos. Consulta gratuita.</em></p>
    <hr style="border: 0; border-top: 1px dashed #e9ebec; margin: 15px 0;">
    @endif

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
