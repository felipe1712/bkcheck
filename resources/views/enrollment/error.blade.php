@extends('enrollment.layout')

@section('content')
<div class="screen active" style="text-align:center; padding-top: 40px;">

    @php
        $icono  = $icono  ?? 'error';
        $titulo = $titulo ?? 'Error';
        $mensaje= $mensaje?? 'Este enlace no es válido.';
        $emoji  = match($icono) {
            'check' => '✅',
            'clock' => '⏰',
            default => '❌',
        };
    @endphp

    <div style="font-size: 68px; margin-bottom: 20px;">{{ $emoji }}</div>

    <div class="screen-title" style="margin-bottom: 12px;">{{ $titulo }}</div>

    <div class="screen-sub" style="max-width: 320px; margin: 0 auto 32px;">
        {{ $mensaje }}
    </div>

    <div class="card" style="text-align: left; background: rgba(239,68,68,.06); border-color: rgba(239,68,68,.2);">
        <div style="display:flex; gap:10px; align-items:flex-start; font-size:13px; color: #fca5a5;">
            <span style="font-size:18px; flex-shrink:0;">ℹ️</span>
            <span>Si crees que esto es un error, por favor contacta a la persona que te compartió este enlace para que genere uno nuevo.</span>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
// Hide progress bar on error screen
document.getElementById('progressBarWrap').style.display = 'none';
</script>
@endsection
