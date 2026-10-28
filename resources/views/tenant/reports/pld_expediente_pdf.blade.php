<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Expediente de Antecedentes — Nivel Due Diligence ({{ $exp['folio'] ?? '' }})</title>
    <style>
        @page { margin: 22px 28px 36px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; line-height: 1.38; }
        h1 { font-size: 14px; margin: 0 0 4px 0; color: #0f172a; text-transform: uppercase; }
        h2 { font-size: 10.5px; background: #0f172a; color: #ffffff; padding: 4px 8px; margin: 10px 0 5px 0; text-transform: uppercase; letter-spacing: 0.4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-weight: bold; color: #0f172a; }
        .badge-alto { background: #dc2626; color: #fff; padding: 2px 6px; font-weight: bold; border-radius: 3px; }
        .badge-medio { background: #f59e0b; color: #000; padding: 2px 6px; font-weight: bold; border-radius: 3px; }
        .badge-bajo { background: #10b981; color: #fff; padding: 2px 6px; font-weight: bold; border-radius: 3px; }
        .box { border: 1px solid #cbd5e1; background: #f8fafc; padding: 6px 8px; margin-bottom: 6px; }
        .mono { font-family: DejaVu Sans Mono, monospace; font-size: 8px; word-break: break-all; }
        .small { font-size: 8.5px; color: #475569; }
    </style>
</head>
<body>

    <div style="border-bottom: 2px solid #0f172a; padding-bottom: 6px; margin-bottom: 8px;">
        <h1>EXPEDIENTE DE ANTECEDENTES — NIVEL DUE DILIGENCE (INSUMO EBR / PLD)</h1>
        <div class="small">
            <strong>AvalID</strong> · Folio: <strong>{{ $exp['folio'] ?? 'No asignado' }}</strong>
            · Versión: <strong>v{{ $exp['version'] ?? 1 }}</strong>
            · Ejecución: <strong>{{ $exp['tipo_ejecucion'] ?? 'INICIAL' }}</strong>
            · Generado: <strong>{{ $exp['fecha_generacion'] ?? '' }}</strong>
        </div>
    </div>

    <h2>INFORMACIÓN GENERAL DEL SUJETO</h2>
    <table>
        <tr>
            <th>Nombre / Razón Social</th>
            <td>{{ $exp['persona']['nombre_o_razon'] ?? 'No provisto' }}</td>
            <th>Tipo de Persona</th>
            <td>{{ $exp['persona']['tipo_persona'] ?? 'No provisto' }}</td>
        </tr>
        <tr>
            <th>RFC</th>
            <td>{{ $exp['persona']['rfc'] ?? 'No provisto' }}</td>
            <th>CURP</th>
            <td>{{ $exp['persona']['curp'] ?? 'No provisto' }}</td>
        </tr>
        <tr>
            <th>Domicilio</th>
            <td colspan="3">{{ $exp['persona']['domicilio'] ?? 'No provisto' }}</td>
        </tr>
    </table>

    <h2>RECOMENDACIÓN DE ACCIÓN: {{ $exp['recomendacion'] ?? 'REVISION_MANUAL' }}</h2>
    <div class="box">
        {{ $exp['recomendacion_justificacion'] ?? '' }}
    </div>

    <h2>EVALUACIÓN MULTÍMETRO POR CATEGORÍAS</h2>
    <table>
        <thead>
            <tr>
                <th>Score Global tras Topes</th>
                <th>Identidad ({{ $exp['scores_categoria']['IDENTIDAD']['peso'] ?? 40 }}%)</th>
                <th>Cumplimiento PLD ({{ $exp['scores_categoria']['PLD']['peso'] ?? 30 }}%)</th>
                <th>Consistencia ({{ $exp['scores_categoria']['CONSISTENCIA']['peso'] ?? 15 }}%)</th>
                <th>Reputacional-OSINT ({{ $exp['scores_categoria']['REPUTACIONAL']['peso'] ?? 15 }}%)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $exp['score_global'] ?? 0 }}%</strong></td>
                <td>{{ $exp['scores_categoria']['IDENTIDAD']['score'] ?? 0 }}% ({{ $exp['scores_categoria']['IDENTIDAD']['estatus'] ?? '' }})</td>
                <td>{{ $exp['scores_categoria']['PLD']['score'] ?? 0 }}% ({{ $exp['scores_categoria']['PLD']['estatus'] ?? '' }})</td>
                <td>{{ $exp['scores_categoria']['CONSISTENCIA']['score'] ?? 0 }}% ({{ $exp['scores_categoria']['CONSISTENCIA']['estatus'] ?? '' }})</td>
                <td>{{ $exp['scores_categoria']['REPUTACIONAL']['score'] ?? 0 }}% ({{ $exp['scores_categoria']['REPUTACIONAL']['estatus'] ?? '' }})</td>
            </tr>
        </tbody>
    </table>

    <h2>NIVEL DE RIESGO DE PERSONA (INSUMO EBR)</h2>
    <div class="box">
        @php $niv = $exp['nivel_riesgo_persona']['nivel'] ?? 'MEDIO'; @endphp
        <p style="margin:2px 0;">
            <strong>Nivel:</strong>
            <span class="{{ $niv === 'ALTO' ? 'badge-alto' : ($niv === 'BAJO' ? 'badge-bajo' : 'badge-medio') }}">{{ $niv }}</span>
        </p>
        <p style="margin:2px 0;">
            <strong>Reglas que lo determinan:</strong>
            @foreach(($exp['nivel_riesgo_persona']['reglas'] ?? []) as $r)
                {{ $r['regla'] ?? '' }} [fuente: {{ $r['fuente'] ?? '' }}, campo: {{ $r['campo'] ?? '' }}]@if(!$loop->last); @endif
            @endforeach
        </p>
        <p style="margin:2px 0;">
            <strong>Debida Diligencia Reforzada recomendada:</strong>
            {{ !empty($exp['edd']['recomendada']) ? 'SÍ' : 'NO' }} — {{ $exp['edd']['causa'] ?? '' }}
        </p>
        <p style="margin:2px 0;">
            <strong>Estatus PEP:</strong> {{ $exp['pep']['estatus'] ?? 'PENDIENTE' }}
            · Fuente: {{ $exp['pep']['fuente'] ?? 'No consultada' }}
            · Fecha: {{ $exp['pep']['fecha'] ?? 'Sin dato' }}
            · Asimilados a PEP: {{ $exp['pep']['asimilados'] ?? 'no evaluado' }}
        </p>
        <p style="margin:2px 0;">
            <strong>Fecha de consulta:</strong> {{ $exp['fecha_generacion'] ?? '' }}
            · <strong>Reevaluar antes de:</strong> {{ $exp['proxima_reevaluacion'] ?? '' }} (intervalo: {{ $exp['intervalo_reevaluacion_meses'] ?? 6 }} meses)
            · <strong>Conservar hasta:</strong> {{ $exp['conservar_hasta'] ?? '' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th colspan="4">Factores inherentes (RCG Art. 23 Bis 2)</th>
            </tr>
            <tr>
                <th>Factor</th>
                <th>Valor</th>
                <th>Fuente</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($exp['factores_inherentes'] ?? []) as $fi)
                <tr>
                    <td>{{ $fi['factor'] ?? '' }}</td>
                    <td>{{ $fi['valor'] ?? 'No provisto' }}</td>
                    <td>{{ $fi['fuente'] ?? 'Sin dato' }}</td>
                    <td>{{ $fi['estado'] ?? 'No evaluado' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="box">
        <strong>{{ $exp['factores_transaccionales']['leyenda_fija'] ?? '' }}</strong>
        <br>Perfil declarado por el cliente: {{ $exp['factores_transaccionales']['declarado_por_cliente'] ?? 'No provisto por el cliente' }}
        @if(($exp['persona']['tipo_persona'] ?? '') === 'MORAL' && !empty($exp['beneficiario_controlador']))
            <br><strong>Beneficiario Controlador:</strong> {{ $exp['beneficiario_controlador']['estado'] ?? '' }} — {{ $exp['beneficiario_controlador']['prelacion_aplicada'] ?? '' }}
        @endif
        @if(($exp['finalidad'] ?? '') === 'seleccion_personal' && !empty($exp['corroboracion_personal']))
            <br><strong>Corroboración de declaración de personal:</strong>
            Declaración: {{ $exp['corroboracion_personal']['declaracion_firmada'] ?? '' }} |
            IMSS: {{ $exp['corroboracion_personal']['corroboracion_trayectoria_imss'] ?? '' }} |
            Honorabilidad: {{ $exp['corroboracion_personal']['delitos_patrimoniales_inhabilitacion'] ?? '' }}
        @endif
    </div>

    <h2>CONTROL DE VERSIÓN</h2>
    <div class="box">
        <strong>Folio:</strong> {{ $exp['meta_version']['folio'] ?? 'No asignado' }}
        · <strong>Versión:</strong> {{ $exp['meta_version']['version'] ?? 1 }}
        · <strong>Tipo de ejecución:</strong> {{ $exp['meta_version']['tipo_ejecucion'] ?? 'INICIAL' }}
        · <strong>Generado:</strong> {{ $exp['meta_version']['fecha_generacion'] ?? '' }}<br>
        <strong>Hash de esta versión:</strong> <span class="mono">{{ $exp['hash_version'] ?? '' }}</span><br>
        <strong>Hash de la versión anterior:</strong> <span class="mono">{{ $exp['meta_version']['hash_previo'] ?? 'no aplica' }}</span>
    </div>

    @if(($exp['meta_version']['tipo_ejecucion'] ?? 'INICIAL') !== 'INICIAL' || !empty($exp['cambios']))
        <h2>CAMBIOS RESPECTO A LA VERSIÓN ANTERIOR</h2>
        @if(empty($exp['cambios']['cambios']))
            <div class="box">Sin cambios respecto a la versión {{ $exp['cambios']['version_previa'] ?? 'previa' }} ({{ $exp['meta_version']['fecha_generacion'] ?? '' }}).</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Campo</th>
                        <th>Antes</th>
                        <th>Después</th>
                        <th>Fuente</th>
                        <th>Fecha de consulta</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($exp['cambios']['items'] ?? []) as $item)
                        <tr>
                            <td>{{ $item['campo'] ?? '' }}</td>
                            <td>{{ is_scalar($item['antes'] ?? null) ? $item['antes'] : json_encode($item['antes'] ?? '') }}</td>
                            <td>{{ is_scalar($item['despues'] ?? null) ? $item['despues'] : json_encode($item['despues'] ?? '') }}</td>
                            <td>{{ $item['fuente'] ?? 'sistema' }}</td>
                            <td>{{ $item['fecha_consulta'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <h2>HALLAZGOS CONFIRMADOS ({{ count($exp['hallazgos'] ?? []) }})</h2>
    @if(empty($exp['hallazgos']))
        <div class="box">Sin hallazgos confirmados en las fuentes consultadas.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Fuente</th>
                    <th>Campo</th>
                    <th>Descripción</th>
                    <th>Efecto en Score</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($exp['hallazgos'] ?? []) as $h)
                    <tr>
                        <td>{{ $h['categoria'] ?? '' }}</td>
                        <td>{{ $h['fuente'] ?? '' }}</td>
                        <td>{{ $h['campo'] ?? '' }}</td>
                        <td>{{ $h['descripcion'] ?? '' }}</td>
                        <td>{{ $h['efecto_score'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>FUENTES PENDIENTES DE VERIFICACIÓN (NO CONCLUYENTES)</h2>
    @if(empty($exp['fuentes_pendientes']))
        <div class="box">Ninguna fuente pendiente de verificación.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Fuente</th>
                    <th>Código HTTP</th>
                    <th>Categoría (Tope)</th>
                    <th>Motivo</th>
                    <th>Acción Recomendada</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($exp['fuentes_pendientes'] ?? []) as $p)
                    <tr>
                        <td>{{ $p['fuente'] ?? '' }}</td>
                        <td>{{ $p['status_http'] ?? '' }}</td>
                        <td>{{ $p['categoria'] ?? '' }} (Tope {{ $p['tope_categoria'] ?? 89 }}%)</td>
                        <td>{{ $p['motivo'] ?? '' }}</td>
                        <td>{{ $p['accion_recomendada'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>CONSENTIMIENTO LEGAL Y CUMPLIMIENTO (LFPDPPP)</h2>
    <div class="box">
        <strong>Estado de carta:</strong> {{ !empty($exp['consentimiento']['otorgado']) ? 'OTORGADO' : 'NO OTORGADO' }}
        | <strong>Fecha de firma:</strong> {{ $exp['consentimiento']['fecha_firma'] ?? 'No provisto' }}
        | <strong>Finalidad/base legal:</strong> {{ $exp['consentimiento']['base_legal'] ?? 'No provisto' }}
    </div>

    <h2>RESUMEN DE RESULTADOS POR FUENTES</h2>
    <table>
        <thead>
            <tr>
                <th>Fuente</th>
                <th>Estatus Consulta</th>
                <th>Resultado General</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($exp['resumen_fuentes'] ?? []) as $rf)
                <tr>
                    <td>{{ $rf['fuente'] ?? '' }}</td>
                    <td>{{ $rf['estado_consulta'] ?? '' }}</td>
                    <td>{{ $rf['resultado_general'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>DETALLE POR FUENTE</h2>
    @foreach(($exp['resumen_fuentes'] ?? []) as $rf)
        @php
            $crudo = $rf['resultado_crudo'] ?? [];
            unset($crudo['mrz'], $crudo['raw_ocr']);
        @endphp
        @if(!empty($crudo))
            <div class="box">
                <strong>[{{ strtoupper($rf['fuente'] ?? '') }}]</strong> ({{ $rf['fecha_consulta'] ?? '' }}):
                <div class="mono">{{ json_encode($crudo, JSON_UNESCAPED_UNICODE) }}</div>
            </div>
        @endif
    @endforeach

    <h2>DESCARGO DE RESPONSABILIDAD</h2>
    <div class="box small">
        {{ $exp['descargo_responsabilidad'] ?? '' }}
    </div>

</body>
</html>
