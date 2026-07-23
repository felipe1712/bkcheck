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
{{-- Cropper.js CDN --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

{{-- Modal de recorte --}}
<div id="cropModal" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,.92); flex-direction:column;
    align-items:center; justify-content:center; padding:12px;">
    <div style="width:100%; max-width:520px; background:#1a1f2e; border-radius:16px;">
        <div style="padding:12px 18px; border-bottom:1px solid #2e3550; display:flex; justify-content:space-between; align-items:center;">
            <span id="cropModalTitle" style="color:#e8eaf0; font-weight:600; font-size:15px;"></span>
            <span style="color:#8892a4; font-size:11px;">Ajusta los bordes y confirma</span>
        </div>
        {{-- Contenedor del cropper: altura fija, SIN overflow:hidden --}}
        <div id="cropContainer" style="background:#0f1117; height:55vh; position:relative;">
            <img id="cropImg" alt="">
        </div>
        <div style="padding:12px 18px; display:flex; gap:10px;">
            <button id="btnCropCancel" style="
                flex:1; padding:13px; background:transparent; border:1px solid #2e3550;
                border-radius:10px; color:#8892a4; font-size:14px; font-weight:600; cursor:pointer;">
                Cancelar
            </button>
            <button id="btnCropConfirm" style="
                flex:2; padding:13px;
                background:linear-gradient(135deg,#4f6ef7,#3a55e0);
                border:none; border-radius:10px; color:#fff;
                font-size:15px; font-weight:600; cursor:pointer;">
                ✂️ Confirmar recorte
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const TOKEN       = @json($token);
    const CSRF        = document.querySelector('meta[name="csrf-token"]').content;
    const TOTAL_STEPS = 5;

    let currentStep = 0;
    let fileFrente  = null;
    let fileReverso = null;
    let fileSelfie  = null;

    EnrollApp.renderProgress(0, TOTAL_STEPS);

    // ── Helpers ─────────────────────────────────────────────────────────────
    function showScreen(id, step) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        currentStep = step;
        EnrollApp.renderProgress(step, TOTAL_STEPS);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function showError(id) {
        const el = document.getElementById(id);
        if (el) { el.classList.add('show'); setTimeout(() => el.classList.remove('show'), 5000); }
    }

    function hideError(id) {
        document.getElementById(id)?.classList.remove('show');
    }

    function previewPhoto(slot, file) {
        if (!file) return;
        const url = URL.createObjectURL(file);
        slot.innerHTML = `<img src="${url}" alt="Previsualización"><span class="slot-badge">✓ Listo</span>`;
        slot.classList.add('has-photo');
    }

    // ── Cropper.js helper ───────────────────────────────────────────────────
    let cropperInstance = null;
    let cropCallback    = null;

    function openCropper(file, title, aspectRatio, callback) {
        const modal    = document.getElementById('cropModal');
        const cropImg  = document.getElementById('cropImg');
        const container = document.getElementById('cropContainer');
        document.getElementById('cropModalTitle').textContent = title;

        // Destruir instancia previa antes de limpiar el src
        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }
        cropImg.src = '';

        const reader = new FileReader();
        reader.onload = (e) => {
            // Mostrar el modal ANTES de inicializar el cropper
            // para que el contenedor tenga dimensiones reales en el DOM
            modal.style.display = 'flex';

            cropImg.onload = () => {
                // Inicializar SOLO cuando la imagen ya está cargada y el modal visible
                cropperInstance = new Cropper(cropImg, {
                    aspectRatio:       aspectRatio,
                    viewMode:          1,
                    dragMode:          'move',
                    autoCropArea:      1,
                    restore:           false,
                    guides:            true,
                    center:            true,
                    highlight:         false,
                    cropBoxMovable:    true,
                    cropBoxResizable:  true,
                    checkOrientation:  true,
                    // Altura fija para que Cropper no desborde
                    minContainerHeight: container.offsetHeight,
                    maxContainerHeight: container.offsetHeight,
                    toggleDragModeOnDblclick: false,
                });
                cropImg.onload = null; // evitar doble disparo
            };
            cropImg.src = e.target.result;
        };
        reader.readAsDataURL(file);
        cropCallback = callback;
    }

    document.getElementById('btnCropConfirm').addEventListener('click', () => {
        if (!cropperInstance || !cropCallback) return;
        cropperInstance.getCroppedCanvas({ maxWidth: 1200, maxHeight: 900, imageSmoothingQuality: 'high' })
            .toBlob((blob) => {
                document.getElementById('cropModal').style.display = 'none';
                cropperInstance.destroy();
                cropperInstance = null;
                cropCallback(blob);
                cropCallback = null;
            }, 'image/jpeg', 0.88);
    });

    document.getElementById('btnCropCancel').addEventListener('click', () => {
        document.getElementById('cropModal').style.display = 'none';
        if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
        cropCallback = null;
    });

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
            EnrollApp.hideLoader();
            showScreen('screen-ine-frente', 1);
        } catch (e) {
            EnrollApp.hideLoader();
            showError('tcError');
        }
    });

    document.getElementById('btnRejectTc').addEventListener('click', () => {
        document.getElementById('tcError').textContent = 'No es posible continuar sin aceptar los términos.';
        showError('tcError');
    });

    // ── Pantalla 1: INE Frente (con recorte) ────────────────────────────────
    document.getElementById('inputFrente').addEventListener('change', function () {
        const raw = this.files[0];
        if (!raw) return;
        // Aspect ratio INE: 85.6mm × 54mm = 1.5852
        openCropper(raw, '📸 Recorta el frente de tu INE', 85.6 / 54, (croppedBlob) => {
            fileFrente = new File([croppedBlob], 'ine_frente.jpg', { type: 'image/jpeg' });
            previewPhoto(document.getElementById('slotFrente'), fileFrente);
            document.getElementById('btnFrenteContinuar').disabled = false;
            hideError('frenteError');
        });
        this.value = ''; // reset input para permitir re-selección
    });

    document.getElementById('btnFrenteContinuar').addEventListener('click', () => {
        if (!fileFrente) { showError('frenteError'); return; }
        showScreen('screen-ine-reverso', 2);
    });

    // ── Pantalla 2: INE Reverso (con recorte) ───────────────────────────────
    document.getElementById('inputReverso').addEventListener('change', function () {
        const raw = this.files[0];
        if (!raw) return;
        openCropper(raw, '🔄 Recorta el reverso de tu INE', 85.6 / 54, (croppedBlob) => {
            fileReverso = new File([croppedBlob], 'ine_reverso.jpg', { type: 'image/jpeg' });
            previewPhoto(document.getElementById('slotReverso'), fileReverso);
            document.getElementById('btnReversoContinuar').disabled = false;
            hideError('reversoError');
        });
        this.value = '';
    });

    document.getElementById('btnReversoContinuar').addEventListener('click', () => {
        if (!fileReverso) { showError('reversoError'); return; }
        showScreen('screen-selfie', 3);
    });

    document.getElementById('btnReversoBack').addEventListener('click', () => {
        showScreen('screen-ine-frente', 1);
    });

    // ── Pantalla 3: Selfie (recorte libre 1:1) ──────────────────────────────
    document.getElementById('inputSelfie').addEventListener('change', function () {
        const raw = this.files[0];
        if (!raw) return;
        openCropper(raw, '🤳 Ajusta tu selfie', 1, (croppedBlob) => {
            fileSelfie = new File([croppedBlob], 'selfie.jpg', { type: 'image/jpeg' });
            previewPhoto(document.getElementById('slotSelfie'), fileSelfie);
            document.getElementById('btnEnviar').disabled = false;
            hideError('selfieError');
        });
        this.value = '';
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
            el.textContent = `Las imágenes son demasiado grandes (${(totalSize/1024/1024).toFixed(1)} MB). El límite es 20 MB.`;
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
            rawText = await res.text();
        } catch (networkErr) {
            EnrollApp.hideLoader();
            const el = document.getElementById('uploadError');
            el.textContent = 'Error de conexión. Verifica tu internet y vuelve a intentarlo.';
            showError('uploadError');
            return;
        }

        EnrollApp.hideLoader();

        let data = null;
        try {
            data = JSON.parse(rawText);
        } catch (_) {
            const el = document.getElementById('uploadError');
            if (res.status === 419) {
                el.textContent = 'Tu sesión expiró. Por favor recarga la página y vuelve a intentarlo.';
            } else if (res.status === 413) {
                el.textContent = 'Las imágenes son demasiado grandes para el servidor. Intenta de nuevo.';
            } else {
                el.textContent = `Error del servidor (${res.status}). Por favor intenta de nuevo.`;
            }
            showError('uploadError');
            return;
        }

        if (res.ok && data.status === 'ok') {
            EnrollApp.showLoader('¡Listo! Redirigiendo…');
            window.location.href = data.redirect;
        } else {
            const el = document.getElementById('uploadError');
            el.textContent = data.error || 'Ocurrió un error. Por favor intenta de nuevo.';
            showError('uploadError');
        }
    });
});
</script>
@endsection
