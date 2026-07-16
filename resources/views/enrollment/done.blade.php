@extends('enrollment.layout')

@section('content')
<div class="screen active" style="text-align:center; padding-top: 32px;">

    <div style="font-size: 72px; margin-bottom: 20px; animation: popIn .6s cubic-bezier(.175,.885,.32,1.275);">
        ✅
    </div>

    <div class="screen-title" style="margin-bottom: 12px;">¡Información enviada!</div>

    <div class="screen-sub" style="margin-bottom: 32px; max-width: 320px; margin-left: auto; margin-right: auto;">
        Tu información fue enviada correctamente y ya está siendo verificada.<br><br>
        <strong>Ya puedes cerrar esta ventana.</strong>
    </div>

    <div class="card" style="text-align: left; background: rgba(34,197,94,.07); border-color: rgba(34,197,94,.2);">
        <div style="display:flex; gap:10px; align-items:flex-start; font-size:13px; color: #86efac;">
            <span style="font-size:18px; flex-shrink:0;">🔒</span>
            <span>Tus datos están protegidos y serán tratados conforme a la Ley Federal de Protección de Datos Personales (LFPDPPP).</span>
        </div>
    </div>

    <div style="margin-top: 24px; color: var(--muted); font-size: 12px;">
        Verificación procesada · BkCheck
    </div>

</div>
@endsection

@section('scripts')
<script>
// Hide progress bar on done screen
document.getElementById('progressBarWrap').style.display = 'none';
</script>
@endsection
