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
{{-- Modal Overlay para Liveness Session --}}

{{-- Modal Overlay para Liveness Session --}}
<div id="livenessModal" style="
    display:none; position:fixed; inset:0; z-index:10000;
    background:#0f1117; flex-direction:column;">
    <div style="padding:12px 18px; background:#1a1f2e; border-bottom:1px solid #2e3550; display:flex; justify-content:space-between; align-items:center;">
        <span style="color:#fff; font-weight:bold; font-size:15px;">📹 Prueba de Vida en Vivo</span>
        <div style="display:flex; gap:10px; align-items:center;">
            <a id="btnOpenLivenessNewTab" href="#" target="_blank" style="padding:6px 12px; background:rgba(79,110,247,0.2); border:1px solid #4f6ef7; border-radius:6px; color:#4f6ef7; font-size:12px; font-weight:bold; text-decoration:none;">
                ↗ Pantalla Completa
            </a>
            <button id="btnCloseLivenessModal" style="background:transparent; border:none; color:#8892a4; font-size:22px; cursor:pointer;">✕</button>
        </div>
    </div>
    <iframe id="livenessIframe" src="" 
        style="width:100%; height:100%; border:none; background:#0f1117;"
        allow="camera *; microphone *; autoplay *; camera; microphone; display-capture; geolocation"
        allowfullscreen
        playsinline>
    </iframe>
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

    /**
     * Procesa y optimiza la foto completa conservando el 100% de la imagen (0% recorte).
     * Soporta rotación de 0, 90, 180, 270 grados en HTML5 Canvas.
     */
    function processFullImage(file, rotationDegrees = 0) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;

                    const maxDim = 1920;
                    if (width > maxDim || height > maxDim) {
                        if (width > height) {
                            height = Math.round((height * maxDim) / width);
                            width = maxDim;
                        } else {
                            width = Math.round((width * maxDim) / height);
                            height = maxDim;
                        }
                    }

                    const rad = ((rotationDegrees % 360) * Math.PI) / 180;
                    if ((rotationDegrees / 90) % 2 !== 0) {
                        canvas.width = height;
                        canvas.height = width;
                    } else {
                        canvas.width = width;
                        canvas.height = height;
                    }

                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);

                    ctx.save();
                    ctx.translate(canvas.width / 2, canvas.height / 2);
                    ctx.rotate(rad);
                    ctx.drawImage(img, -width / 2, -height / 2, width, height);
                    ctx.restore();

                    canvas.toBlob((blob) => {
                        resolve(blob);
                    }, 'image/jpeg', 0.92);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    function previewPhoto(slot, file, onRotate = null) {
        if (!file) return;
        const url = URL.createObjectURL(file);
        slot.innerHTML = `
            <div style="display: flex; flex-direction: column; align-items: center; width: 100%; gap: 8px;">
                <img src="${url}" alt="Previsualización" style="max-height: 220px; object-fit: contain; width: 100%; border-radius: 8px; border: 1px solid #2e3550;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <span class="slot-badge" style="position: static; display: inline-flex; align-items: center; gap: 4px; background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid #22c55e; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold;">
                        ✓ Foto Completa (Sin recorte)
                    </span>
                    ${onRotate ? `<button type="button" class="btn-rotate-photo" style="background: rgba(255, 255, 255, 0.1); border: 1px solid #3e4868; color: #e8eaf0; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">🔄 Rotar 90°</button>` : ''}
                </div>
            </div>
        `;
        slot.classList.add('has-photo');

        if (onRotate) {
            const btn = slot.querySelector('.btn-rotate-photo');
            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    onRotate();
                });
            }
        }
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
    let rawFrenteFile = null;
    let frenteRotation = 0;

    async function handleFrenteSelected(rawFile) {
        rawFrenteFile = rawFile;
        EnrollApp.showLoader('Optimizando foto completa...');
        const blob = await processFullImage(rawFrenteFile, frenteRotation);
        fileFrente = new File([blob], 'ine_frente.jpg', { type: 'image/jpeg' });
        previewPhoto(document.getElementById('slotFrente'), fileFrente, async () => {
            frenteRotation = (frenteRotation + 90) % 360;
            await handleFrenteSelected(rawFrenteFile);
        });
        document.getElementById('btnFrenteContinuar').disabled = false;
        hideError('frenteError');
        EnrollApp.hideLoader();
    }

    document.getElementById('inputFrente').addEventListener('change', function () {
        if (this.files[0]) {
            frenteRotation = 0;
            handleFrenteSelected(this.files[0]);
        }
        this.value = '';
    });

    document.getElementById('btnFrenteContinuar').addEventListener('click', () => {
        if (!fileFrente) { showError('frenteError'); return; }
        showScreen('screen-ine-reverso', 2);
    });

    // ── Pantalla 2: INE Reverso ──────────────────────────────────────────────
    let rawReversoFile = null;
    let reversoRotation = 0;

    async function handleReversoSelected(rawFile) {
        rawReversoFile = rawFile;
        EnrollApp.showLoader('Optimizando foto completa...');
        const blob = await processFullImage(rawReversoFile, reversoRotation);
        fileReverso = new File([blob], 'ine_reverso.jpg', { type: 'image/jpeg' });
        previewPhoto(document.getElementById('slotReverso'), fileReverso, async () => {
            reversoRotation = (reversoRotation + 90) % 360;
            await handleReversoSelected(rawReversoFile);
        });
        document.getElementById('btnReversoContinuar').disabled = false;
        hideError('reversoError');
        EnrollApp.hideLoader();
    }

    document.getElementById('inputReverso').addEventListener('change', function () {
        if (this.files[0]) {
            reversoRotation = 0;
            handleReversoSelected(this.files[0]);
        }
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
    let rawSelfieFile = null;
    let selfieRotation = 0;

    async function handleSelfieSelected(rawFile) {
        rawSelfieFile = rawFile;
        EnrollApp.showLoader('Optimizando selfie completa...');
        const blob = await processFullImage(rawSelfieFile, selfieRotation);
        fileSelfie = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
        previewPhoto(document.getElementById('slotSelfie'), fileSelfie, async () => {
            selfieRotation = (selfieRotation + 90) % 360;
            await handleSelfieSelected(rawSelfieFile);
        });
        document.getElementById('btnEnviar').style.display = 'block';
        document.getElementById('btnEnviar').disabled = false;
        hideError('selfieError');
        EnrollApp.hideLoader();
    }

    document.getElementById('inputSelfie').addEventListener('change', function () {
        if (this.files[0]) {
            selfieRotation = 0;
            handleSelfieSelected(this.files[0]);
        }
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
                const modal = document.getElementById('livenessModal');
                const iframe = document.getElementById('livenessIframe');
                const btnNewTab = document.getElementById('btnOpenLivenessNewTab');

                iframe.src = livenessData.url;
                if (btnNewTab) {
                    btnNewTab.href = livenessData.url;
                }
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
