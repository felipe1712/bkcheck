@extends('enrollment.layout')

@section('content')

{{-- ─────────────────────────── PANTALLA 0: T&C ─────────────────────────── --}}
<div class="screen active" id="screen-tc">
    <div class="screen-title">📋 Autorización requerida</div>
    <div class="screen-sub">Antes de continuar, lee y acepta los siguientes términos para que podamos procesar tu verificación de identidad.</div>

    <div class="tc-box" id="tcText">{{ $termsText }}</div>

    <div class="alert-error" id="tcError">Debes leer y aceptar los términos antes de continuar.</div>

    <button class="btn btn-primary" id="btnAcceptTc">
        <span>✅</span> Aceptar y Continuar
    </button>
    <button class="btn btn-ghost" id="btnRejectTc">Rechazar</button>
</div>

{{-- ─────────────────────── PANTALLA 1: INE FRENTE ──────────────────────── --}}
<div class="screen" id="screen-ine-frente">
    <div class="screen-title">📸 INE — Frente</div>
    <div class="screen-sub">Toma una foto clara del <strong>frente</strong> de tu credencial de elector (INE/IFE). Asegúrate de que esté bien iluminada y sin reflejos.</div>

    <div class="instruction-chip">
        <span class="ic-icon">💡</span>
        <span>Coloca la credencial sobre una superficie plana y toma la foto de manera horizontal.</span>
    </div>

    <div class="photo-slot" id="slotFrente" onclick="document.getElementById('inputFrente').click()">
        <span class="slot-icon">🪪</span>
        <div class="slot-text">Toca aquí para tomar la foto</div>
    </div>
    <input type="file" id="inputFrente" accept="image/*" capture="environment">

    <div class="alert-error" id="frenteError">Por favor toma la foto del frente del INE.</div>

    <button class="btn btn-primary" id="btnFrenteContinuar" disabled>
        Continuar <span>→</span>
    </button>
</div>

{{-- ─────────────────────── PANTALLA 2: INE REVERSO ─────────────────────── --}}
<div class="screen" id="screen-ine-reverso">
    <div class="screen-title">🔄 INE — Reverso</div>
    <div class="screen-sub">Ahora toma una foto del <strong>reverso</strong> de tu credencial de elector.</div>

    <div class="instruction-chip">
        <span class="ic-icon">💡</span>
        <span>Mantén la credencial en la misma posición horizontal y asegúrate de capturar todo el reverso.</span>
    </div>

    <div class="photo-slot" id="slotReverso" onclick="document.getElementById('inputReverso').click()">
        <span class="slot-icon">🔄</span>
        <div class="slot-text">Toca aquí para tomar la foto</div>
    </div>
    <input type="file" id="inputReverso" accept="image/*" capture="environment">

    <div class="alert-error" id="reversoError">Por favor toma la foto del reverso del INE.</div>

    <button class="btn btn-primary" id="btnReversoContinuar" disabled>
        Continuar <span>→</span>
    </button>
    <button class="btn btn-ghost" id="btnReversoBack">← Volver</button>
</div>

{{-- ──────────────────────── PANTALLA 3: SELFIE ─────────────────────────── --}}
<div class="screen" id="screen-selfie">
    <div class="screen-title">🤳 Selfie de Verificación</div>
    <div class="screen-sub">Toma una selfie mirando directamente a la cámara para confirmar tu identidad. Asegúrate de estar en un lugar bien iluminado.</div>

    <div class="instruction-chip">
        <span class="ic-icon">ℹ️</span>
        <span>Mira al frente, sin lentes de sol y con el rostro visible. No uses filtros ni ediciones de la imagen.</span>
    </div>

    <div class="photo-slot" id="slotSelfie" onclick="document.getElementById('inputSelfie').click()">
        <span class="slot-icon">🤳</span>
        <div class="slot-text">Toca aquí para tomar tu selfie</div>
    </div>
    {{-- capture="user" activa la cámara frontal en dispositivos móviles --}}
    <input type="file" id="inputSelfie" accept="image/*" capture="user">

    <div class="alert-error" id="selfieError">La selfie es obligatoria para completar la verificación.</div>

    <div class="alert-error" id="uploadError">Ocurrió un error al enviar tu información. Por favor intenta de nuevo.</div>

    <button class="btn btn-success" id="btnEnviar" disabled>
        <span>📤</span> Enviar toda mi información
    </button>
    <button class="btn btn-ghost" id="btnSelfieBack">← Volver</button>

    <div class="security-note">
        🔒 Tu información se transmite de forma cifrada y segura.
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const TOKEN   = @json($token);
    const CSRF    = document.querySelector('meta[name="csrf-token"]').content;
    const TOTAL_STEPS = 5; // TC, Frente, Reverso, Selfie, Done

    let currentStep   = 0;
    let fileFrente    = null;
    let fileReverso   = null;
    let fileSelfie    = null;
    let tcAccepted    = false;

    // Render initial progress
    EnrollApp.renderProgress(0, TOTAL_STEPS);

    // ── Helpers ─────────────────────────────────────────────────────────────
    function showScreen(id, step) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        currentStep = step;
        EnrollApp.renderProgress(step, TOTAL_STEPS);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function hideError(id) {
        document.getElementById(id)?.classList.remove('show');
    }

    function showError(id) {
        const el = document.getElementById(id);
        if (el) { el.classList.add('show'); setTimeout(() => el.classList.remove('show'), 4000); }
    }

    function previewPhoto(slot, file) {
        if (!file) return;
        const url = URL.createObjectURL(file);
        slot.innerHTML = `<img src="${url}" alt="Previsualización"><span class="slot-badge">✓ Listo</span>`;
        slot.classList.add('has-photo');
    }

    // ── Pantalla 0: T&C ─────────────────────────────────────────────────────
    document.getElementById('btnAcceptTc').addEventListener('click', async () => {
        EnrollApp.showLoader('Registrando autorización…');
        try {
            const res = await fetch(`/enroll/${TOKEN}/accept-tc`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({})
            });
            if (!res.ok) throw new Error('Error al registrar la autorización.');
            tcAccepted = true;
            EnrollApp.hideLoader();
            showScreen('screen-ine-frente', 1);
        } catch (e) {
            EnrollApp.hideLoader();
            showError('tcError');
        }
    });

    document.getElementById('btnRejectTc').addEventListener('click', () => {
        document.getElementById('tcError').textContent = 'No es posible continuar sin aceptar los términos. Si tienes dudas, contacta a quien te envió este enlace.';
        showError('tcError');
    });

    // ── Pantalla 1: INE Frente ───────────────────────────────────────────────
    document.getElementById('inputFrente').addEventListener('change', function () {
        fileFrente = this.files[0] || null;
        if (fileFrente) {
            previewPhoto(document.getElementById('slotFrente'), fileFrente);
            document.getElementById('btnFrenteContinuar').disabled = false;
            hideError('frenteError');
        }
    });

    document.getElementById('btnFrenteContinuar').addEventListener('click', () => {
        if (!fileFrente) { showError('frenteError'); return; }
        showScreen('screen-ine-reverso', 2);
    });

    // ── Pantalla 2: INE Reverso ──────────────────────────────────────────────
    document.getElementById('inputReverso').addEventListener('change', function () {
        fileReverso = this.files[0] || null;
        if (fileReverso) {
            previewPhoto(document.getElementById('slotReverso'), fileReverso);
            document.getElementById('btnReversoContinuar').disabled = false;
            hideError('reversoError');
        }
    });

    document.getElementById('btnReversoContinuar').addEventListener('click', () => {
        if (!fileReverso) { showError('reversoError'); return; }
        showScreen('screen-selfie', 3);
    });

    document.getElementById('btnReversoBack').addEventListener('click', () => {
        showScreen('screen-ine-frente', 1);
    });

    // ── Pantalla 3: Selfie ───────────────────────────────────────────────────
    document.getElementById('inputSelfie').addEventListener('change', function () {
        fileSelfie = this.files[0] || null;
        if (fileSelfie) {
            previewPhoto(document.getElementById('slotSelfie'), fileSelfie);
            document.getElementById('btnEnviar').disabled = false;
            hideError('selfieError');
        }
    });

    document.getElementById('btnSelfieBack').addEventListener('click', () => {
        showScreen('screen-ine-reverso', 2);
    });

    // ── Envío final ──────────────────────────────────────────────────────────
    document.getElementById('btnEnviar').addEventListener('click', async () => {
        if (!fileFrente)  { showError('frenteError');  return; }
        if (!fileReverso) { showError('reversoError'); return; }
        if (!fileSelfie)  { showError('selfieError');  return; }

        // Pre-validar tamaño total (límite 20 MB)
        const MAX_BYTES = 20 * 1024 * 1024;
        const totalSize = fileFrente.size + fileReverso.size + fileSelfie.size;
        if (totalSize > MAX_BYTES) {
            const el = document.getElementById('uploadError');
            el.textContent = `Las imágenes son demasiado grandes en total (${(totalSize/1024/1024).toFixed(1)} MB). El límite es 20 MB. Por favor toma fotos con menor resolución.`;
            showError('uploadError');
            return;
        }

        const formData = new FormData();
        formData.append('ine_front', fileFrente);
        formData.append('ine_back',  fileReverso);
        formData.append('selfie',    fileSelfie);
        formData.append('_token',    CSRF);

        EnrollApp.showLoader('Enviando tus documentos de forma segura… (puede tardar unos segundos)');

        let res, rawText;
        try {
            res = await fetch(`/enroll/${TOKEN}/upload`, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body:    formData
            });
            rawText = await res.text();          // Siempre leer como texto primero
        } catch (networkErr) {
            EnrollApp.hideLoader();
            const el = document.getElementById('uploadError');
            el.textContent = 'Error de conexión. Verifica tu internet y vuelve a intentarlo.';
            showError('uploadError');
            return;
        }

        EnrollApp.hideLoader();

        // Intentar parsear como JSON
        let data = null;
        try {
            data = JSON.parse(rawText);
        } catch (_) {
            // El servidor devolvió HTML (error 500, 419 CSRF, etc.)
            const el = document.getElementById('uploadError');
            if (res.status === 419) {
                el.textContent = 'Tu sesión expiró. Por favor recarga la página y vuelve a intentarlo.';
            } else if (res.status === 413) {
                el.textContent = 'Las imágenes son demasiado grandes para el servidor. Intenta con fotos de menor resolución.';
            } else {
                el.textContent = `Error del servidor (${res.status}). Por favor intenta de nuevo o contacta soporte.`;
            }
            showError('uploadError');
            return;
        }

        if (res.ok && data.status === 'ok') {
            EnrollApp.showLoader('¡Listo! Redirigiendo…');
            window.location.href = data.redirect;
        } else {
            const el = document.getElementById('uploadError');
            el.textContent = data.error || 'Ocurrió un error al procesar tu información. Por favor intenta de nuevo.';
            showError('uploadError');
        }
    });
});
</script>
@endsection
