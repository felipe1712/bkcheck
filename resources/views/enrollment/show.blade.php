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
        <span>Coloca la credencial sobre una superficie plana y toma la foto de manera horizontal. Puedes ajustar el recuadro o usar la foto completa.</span>
    </div>

    <div class="photo-slot" id="slotFrente" onclick="document.getElementById('inputFrente').click()">
        <span class="slot-icon">🪪</span>
        <div class="slot-text">Toca aquí para tomar la foto del Frente</div>
    </div>
    <input type="file" id="inputFrente" accept="image/*" capture="environment">

    <div class="alert-error" id="frenteError">Por favor toma la foto del frente del INE.</div>

    <button class="btn btn-primary" id="btnFrenteContinuar" disabled>
        Continuar a Reverso <span>→</span>
    </button>
</div>

{{-- ─────────────────────── PANTALLA 2: INE REVERSO ─────────────────────── --}}
<div class="screen" id="screen-ine-reverso">
    <div class="screen-title">🔄 INE — Reverso</div>
    <div class="screen-sub">Ahora toma una foto del <strong>reverso</strong> de tu credencial de elector.</div>

    <div class="instruction-chip">
        <span class="ic-icon">💡</span>
        <span>Mantén la credencial en la misma posición horizontal y asegúrate de capturar todo el reverso sin cortar bordes.</span>
    </div>

    <div class="photo-slot" id="slotReverso" onclick="document.getElementById('inputReverso').click()">
        <span class="slot-icon">🔄</span>
        <div class="slot-text">Toca aquí para tomar la foto del Reverso</div>
    </div>
    <input type="file" id="inputReverso" accept="image/*" capture="environment">

    <div class="alert-error" id="reversoError">Por favor toma la foto del reverso del INE.</div>

    <button class="btn btn-primary" id="btnReversoContinuar" disabled>
        Continuar a Prueba de Vida <span>→</span>
    </button>
    <button class="btn btn-ghost" id="btnReversoBack">← Volver al Frente</button>
</div>

{{-- ──────────────────────── PANTALLA 3: LIVENESS / SELFIE ─────────────────────────── --}}
<div class="screen" id="screen-selfie">
    <div class="screen-title">📹 Prueba de Vida y Verificación Biométrica</div>
    <div class="screen-sub">Inicia la verificación biométrica facial en tiempo real para confirmar tu identidad.</div>

    <div class="instruction-chip">
        <span class="ic-icon">ℹ️</span>
        <span>Ubícate en un espacio bien iluminado, sin lentes de sol y mirando hacia la cámara.</span>
    </div>

    <button class="btn btn-primary" id="btnIniciarLiveness" style="width:100%; padding:15px; font-size:16px; font-weight:bold; background:linear-gradient(135deg,#0066cc,#004499); border:none; margin-bottom:15px;">
        <span>📹</span> Iniciar Prueba de Vida en Vivo
    </button>

    <div style="text-align:center; margin:15px 0; color:#8892a4; font-size:12px;">— O bien, subir fotografía de selfie de respaldo —</div>

    <div class="photo-slot" id="slotSelfie" onclick="document.getElementById('inputSelfie').click()">
        <span class="slot-icon">🤳</span>
        <div class="slot-text">Toca aquí para tomar tu selfie manualmente</div>
    </div>
    <input type="file" id="inputSelfie" accept="image/*" capture="user">

    <div class="alert-error" id="selfieError">Realiza la Prueba de Vida o toma tu selfie para finalizar.</div>
    <div class="alert-error" id="uploadError">Ocurrió un error al enviar tu información. Por favor intenta de nuevo.</div>

    <button class="btn btn-success" id="btnEnviar" disabled style="display:none; width:100%; margin-top:10px;">
        <span>📤</span> Enviar toda mi información
    </button>
    <button class="btn btn-ghost" id="btnSelfieBack" style="margin-top:10px;">← Volver al Reverso</button>

    <div class="security-note" style="margin-top:20px;">
        🔒 Tu información se transmite de forma cifrada y segura.
    </div>
</div>

@endsection

@section('scripts')
{{-- Cropper.js CDN --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

{{-- Modal de recorte ajustado --}}
<div id="cropModal" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,.92); flex-direction:column;
    align-items:center; justify-content:center; padding:12px;">
    <div style="width:100%; max-width:540px; background:#1a1f2e; border-radius:16px; overflow:hidden;">
        <div style="padding:12px 18px; border-bottom:1px solid #2e3550; display:flex; justify-content:space-between; align-items:center;">
            <span id="cropModalTitle" style="color:#e8eaf0; font-weight:600; font-size:15px;"></span>
            <span style="color:#8892a4; font-size:11px;">Ajusta o usa la foto completa</span>
        </div>
        {{-- Contenedor del cropper --}}
        <div id="cropContainer" style="background:#0f1117; height:50vh; position:relative;">
            <img id="cropImg" alt="" style="max-width:100%; display:block;">
        </div>
        <div style="padding:12px 18px; display:flex; flex-direction:column; gap:8px;">
            <button id="btnCropConfirm" style="
                width:100%; padding:13px;
                background:linear-gradient(135deg,#4f6ef7,#3a55e0);
                border:none; border-radius:10px; color:#fff;
                font-size:15px; font-weight:600; cursor:pointer;">
                ✂️ Confirmar Recorte
            </button>
            <div style="display:flex; gap:8px;">
                <button id="btnCropOriginal" style="
                    flex:1; padding:10px; background:rgba(255,255,255,0.08); border:1px solid #3e4868;
                    border-radius:8px; color:#e8eaf0; font-size:12px; font-weight:600; cursor:pointer;">
                    📷 Usar foto completa (Sin recortar)
                </button>
                <button id="btnCropCancel" style="
                    padding:10px 16px; background:transparent; border:1px solid #2e3550;
                    border-radius:8px; color:#8892a4; font-size:12px; font-weight:600; cursor:pointer;">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Overlay para Liveness Session --}}
<div id="livenessModal" style="
    display:none; position:fixed; inset:0; z-index:10000;
    background:#0f1117; flex-direction:column;">
    <div style="padding:12px 20px; background:#1a1f2e; border-bottom:1px solid #2e3550; display:flex; justify-content:space-between; align-items:center;">
        <span style="color:#fff; font-weight:bold; font-size:15px;">📹 Prueba de Vida en Vivo</span>
        <button id="btnCloseLivenessModal" style="background:transparent; border:none; color:#8892a4; font-size:20px; cursor:pointer;">✕</button>
    </div>
        <button id="btnCloseLivenessModal" style="background:transparent; border:none; color:#8892a4; font-size:20px; cursor:pointer;">✕</button>
    </div>
    <iframe id="livenessIframe" src="" style="width:100%; height:100%; border:none;"></iframe>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const TOKEN       = @json($token);
    const CSRF        = document.querySelector('meta[name="csrf-token"]').content;
    const TOTAL_STEPS = 4;

    let currentStep    = 0;
    let fileFrente     = null;
    let fileReverso    = null;
    let fileSelfie     = null;
    let currentRawFile = null;

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
        currentRawFile = file;
        const modal     = document.getElementById('cropModal');
        const cropImg   = document.getElementById('cropImg');
        const container = document.getElementById('cropContainer');
        document.getElementById('cropModalTitle').textContent = title;

        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }
        cropImg.src = '';

        const reader = new FileReader();
        reader.onload = (e) => {
            modal.style.display = 'flex';

            cropImg.onload = () => {
                cropperInstance = new Cropper(cropImg, {
                    aspectRatio:       aspectRatio,
                    viewMode:          0, // 0 = libre sin cortar bordes automáticamente
                    dragMode:          'crop',
                    autoCropArea:      0.98, // 98% con margen holgado
                    restore:           false,
                    guides:            true,
                    center:            true,
                    highlight:         false,
                    cropBoxMovable:    true,
                    cropBoxResizable:  true,
                    checkOrientation:  true,
                    toggleDragModeOnDblclick: false,
                });
                cropImg.onload = null;
            };
            cropImg.src = e.target.result;
        };
        reader.readAsDataURL(file);
        cropCallback = callback;
    }

    document.getElementById('btnCropConfirm').addEventListener('click', () => {
        if (!cropperInstance || !cropCallback) return;
        cropperInstance.getCroppedCanvas({ maxWidth: 1600, maxHeight: 1200, imageSmoothingQuality: 'high' })
            .toBlob((blob) => {
                document.getElementById('cropModal').style.display = 'none';
                cropperInstance.destroy();
                cropperInstance = null;
                cropCallback(blob);
                cropCallback = null;
            }, 'image/jpeg', 0.92);
    });

    document.getElementById('btnCropOriginal').addEventListener('click', () => {
        document.getElementById('cropModal').style.display = 'none';
        if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
        if (currentRawFile && cropCallback) {
            cropCallback(currentRawFile); // Usar foto original directamente sin recortar
            cropCallback = null;
        }
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

    // ── Pantalla 1: INE Frente ──────────────────────────────────────────────
    document.getElementById('inputFrente').addEventListener('change', function () {
        const raw = this.files[0];
        if (!raw) return;
        openCropper(raw, '📸 Ajusta el frente de tu INE', 85.6 / 54, (croppedBlob) => {
            fileFrente = new File([croppedBlob], 'ine_frente.jpg', { type: 'image/jpeg' });
            previewPhoto(document.getElementById('slotFrente'), fileFrente);
            document.getElementById('btnFrenteContinuar').disabled = false;
            hideError('frenteError');
        });
        this.value = '';
    });

    document.getElementById('btnFrenteContinuar').addEventListener('click', () => {
        if (!fileFrente) { showError('frenteError'); return; }
        showScreen('screen-ine-reverso', 2);
    });

    // ── Pantalla 2: INE Reverso ──────────────────────────────────────────────
    document.getElementById('inputReverso').addEventListener('change', function () {
        const raw = this.files[0];
        if (!raw) return;
        openCropper(raw, '🔄 Ajusta el reverso de tu INE', 85.6 / 54, (croppedBlob) => {
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

    // ── Pantalla 3: Selfie Opcional ─────────────────────────────────────────
    document.getElementById('inputSelfie').addEventListener('change', function () {
        const raw = this.files[0];
        if (!raw) return;
        openCropper(raw, '🤳 Ajusta tu selfie', 1, (croppedBlob) => {
            fileSelfie = new File([croppedBlob], 'selfie.jpg', { type: 'image/jpeg' });
            previewPhoto(document.getElementById('slotSelfie'), fileSelfie);
            document.getElementById('btnEnviar').style.display = 'block';
            document.getElementById('btnEnviar').disabled = false;
            hideError('selfieError');
        });
        this.value = '';
    });

    document.getElementById('btnSelfieBack').addEventListener('click', () => {
        showScreen('screen-ine-reverso', 2);
    });

    // ── Iniciar Prueba de Vida NuFi (POST /liveness/V1/alta_consulta) ───────
    document.getElementById('btnIniciarLiveness').addEventListener('click', async () => {
        if (!fileFrente)  { showError('frenteError');  return; }
        if (!fileReverso) { showError('reversoError'); return; }

        EnrollApp.showLoader('Guardando documentos de identidad…');

        // 1. Guardar primero imágenes del INE
        const formData = new FormData();
        formData.append('ine_front', fileFrente);
        formData.append('ine_back',  fileReverso);
        if (fileSelfie) {
            formData.append('selfie', fileSelfie);
        }
        formData.append('_token', CSRF);

        try {
            const uploadRes = await fetch(`/enroll/${TOKEN}/upload`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: formData
            });

            if (!uploadRes.ok) {
                const errJson = await uploadRes.json();
                throw new Error(errJson.error || 'Error al guardar imágenes de la credencial.');
            }
        } catch (err) {
            EnrollApp.hideLoader();
            const el = document.getElementById('uploadError');
            el.textContent = err.message;
            showError('uploadError');
            return;
        }

        // 2. Iniciar sesión Liveness con NuFi API POST /liveness/V1/alta_consulta
        EnrollApp.showLoader('Iniciando sesión biométrica…');

        try {
            const livenessRes = await fetch(`/enroll/${TOKEN}/liveness-session`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({})
            });

            const livenessData = await livenessRes.json();
            EnrollApp.hideLoader();

            if (livenessRes.ok && livenessData.url) {
                // Abrir NuFi Liveness en modal iframe u orientar redirigiendo
                const modal = document.getElementById('livenessModal');
                const iframe = document.getElementById('livenessIframe');
                iframe.src = livenessData.url;
                modal.style.display = 'flex';
            } else {
                throw new Error(livenessData.error || 'No se pudo generar el enlace de Prueba de Vida.');
            }
        } catch (err) {
            EnrollApp.hideLoader();
            const el = document.getElementById('uploadError');
            el.textContent = err.message;
            showError('uploadError');
        }
    });

    document.getElementById('btnCloseLivenessModal').addEventListener('click', () => {
        document.getElementById('livenessModal').style.display = 'none';
        document.getElementById('livenessIframe').src = '';
    });

    // ── Envío alternativo directo ────────────────────────────────────────────
    document.getElementById('btnEnviar').addEventListener('click', async () => {
        if (!fileFrente)  { showError('frenteError');  return; }
        if (!fileReverso) { showError('reversoError'); return; }

        const formData = new FormData();
        formData.append('ine_front', fileFrente);
        formData.append('ine_back',  fileReverso);
        if (fileSelfie) {
            formData.append('selfie', fileSelfie);
        }
        formData.append('_token', CSRF);

        EnrollApp.showLoader('Enviando tus documentos de forma segura…');

        try {
            const res = await fetch(`/enroll/${TOKEN}/upload`, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body:    formData
            });
            const data = await res.json();
            EnrollApp.hideLoader();

            if (res.ok && data.status === 'ok') {
                window.location.href = `/enroll/${TOKEN}/liveness-done`;
            } else {
                showError('uploadError');
            }
        } catch (_) {
            EnrollApp.hideLoader();
            showError('uploadError');
        }
    });
});
</script>
@endsection
