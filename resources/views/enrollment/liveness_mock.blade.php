@extends('enrollment.layout')

@section('content')
<div class="screen active">
    <div class="screen-title">📹 Prueba de Vida en Vivo</div>
    <div class="screen-sub">Sesión Biométrica de Verificación Facial en Tiempo Real</div>

    <div style="background:#1a1f2e; border:1px solid #2e3550; border-radius:16px; p-3 text-center overflow:hidden; margin-bottom:20px; position:relative; min-height:300px; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#fff;">
        <div style="position:absolute; top:12px; right:12px; background:rgba(0,0,0,0.6); padding:4px 10px; border-radius:20px; font-size:11px; font-weight:bold; color:#00f090;">
            ● SESIÓN ACTIVA (ID: {{ $idValidacion }})
        </div>

        <div style="width:140px; height:180px; border:3px dashed #4f6ef7; border-radius:50%; margin:20px auto; display:flex; align-items:center; justify-content:center; background:rgba(79,110,247,0.1);">
            <span style="font-size:48px;">👤</span>
        </div>

        <h3 style="font-size:16px; font-weight:bold; color:#e8eaf0; margin-bottom:6px;">Por favor, mira hacia la cámara</h3>
        <p style="font-size:12px; color:#8892a4; max-width:320px; margin:0 auto 16px;">Sigue las instrucciones en pantalla para verificar que eres una persona real.</p>

        <div style="background:rgba(255,255,255,0.05); padding:8px 16px; border-radius:8px; font-size:12px; color:#4f6ef7; font-weight:bold; margin-bottom:15px;">
            👀 Pestañea lentamente y sonríe
        </div>
    </div>

    <a href="{{ route('enroll.liveness-done', $token) }}" class="btn btn-success" style="width:100%; justify-content:center;">
        <span>✅</span> Confirmar Verificación Biométrica Exitosa
    </a>
</div>
@endsection
