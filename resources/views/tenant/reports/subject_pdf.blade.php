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
        @endif
        <hr style="border: 0; border-top: 1px dashed #e9ebec; margin: 15px 0;">
    @endforeach

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
