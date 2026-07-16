<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1a1f2e">
    <title>Verificación de Identidad</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:     #4f6ef7;
            --primary-dark:#3a55e0;
            --success:     #22c55e;
            --warning:     #f59e0b;
            --danger:      #ef4444;
            --bg:          #0f1117;
            --surface:     #1a1f2e;
            --surface2:    #242938;
            --border:      #2e3550;
            --text:        #e8eaf0;
            --muted:       #8892a4;
            --radius:      16px;
        }

        html, body {
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .enroll-wrap {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            max-width: 480px;
            margin: 0 auto;
            padding: 0 0 env(safe-area-inset-bottom, 24px);
        }

        .enroll-header {
            padding: 20px 24px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border);
        }

        .enroll-header .logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .enroll-header .logo-icon svg { width: 20px; height: 20px; fill: #fff; }
        .enroll-header .brand { font-size: 15px; font-weight: 600; color: var(--text); }
        .enroll-header .brand span { color: var(--muted); font-weight: 400; }

        /* Progress bar */
        .progress-bar-wrap {
            padding: 16px 24px 0;
        }

        .steps-row {
            display: flex;
            align-items: center;
            gap: 0;
        }

        .step-dot {
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700;
            flex-shrink: 0;
            transition: all .3s;
            background: var(--surface2);
            color: var(--muted);
            border: 2px solid var(--border);
        }

        .step-dot.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79,110,247,.2);
        }

        .step-dot.done {
            background: var(--success);
            color: #fff;
            border-color: var(--success);
        }

        .step-dot.done::before { content: '✓'; }

        .step-line {
            flex: 1;
            height: 2px;
            background: var(--border);
            transition: background .3s;
        }

        .step-line.done { background: var(--success); }

        .steps-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 6px;
        }

        .step-label {
            font-size: 9px;
            color: var(--muted);
            text-align: center;
            width: 28px;
            line-height: 1.2;
        }

        .step-label.active { color: var(--primary); font-weight: 600; }
        .step-label.done   { color: var(--success); }

        /* Main content area */
        .enroll-body {
            flex: 1;
            padding: 24px 24px 32px;
            overflow-y: auto;
        }

        .screen { display: none; animation: fadeSlide .35s ease; }
        .screen.active { display: block; }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* Cards */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 16px;
        }

        .screen-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
            line-height: 1.2;
        }

        .screen-sub {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        /* T&C scroll box */
        .tc-box {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            max-height: 340px;
            overflow-y: auto;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.7;
            margin-bottom: 20px;
            white-space: pre-wrap;
        }

        .tc-box::-webkit-scrollbar { width: 4px; }
        .tc-box::-webkit-scrollbar-track { background: transparent; }
        .tc-box::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        /* Buttons */
        .btn {
            width: 100%;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            -webkit-tap-highlight-color: transparent;
        }

        .btn:active { transform: scale(.97); }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 4px 20px rgba(79,110,247,.35);
        }

        .btn-primary:hover { filter: brightness(1.08); }
        .btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; }

        .btn-ghost {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
            margin-top: 10px;
            font-size: 14px;
            padding: 12px;
        }

        .btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            box-shadow: 0 4px 20px rgba(34,197,94,.3);
        }

        /* Photo capture card */
        .photo-slot {
            background: var(--surface2);
            border: 2px dashed var(--border);
            border-radius: 14px;
            padding: 28px 16px;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            position: relative;
            margin-bottom: 16px;
        }

        .photo-slot:hover, .photo-slot.has-photo { border-color: var(--primary); border-style: solid; }
        .photo-slot.has-photo { padding: 0; overflow: hidden; border-radius: 14px; }

        .photo-slot img {
            width: 100%;
            max-height: 240px;
            object-fit: cover;
            border-radius: 12px;
            display: block;
        }

        .photo-slot .slot-icon {
            font-size: 40px;
            margin-bottom: 10px;
            display: block;
        }

        .photo-slot .slot-text {
            color: var(--muted);
            font-size: 13px;
        }

        .photo-slot .slot-badge {
            position: absolute;
            top: 8px; right: 8px;
            background: var(--success);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
        }

        input[type="file"] { display: none; }

        /* Instruction chip */
        .instruction-chip {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: rgba(79,110,247,.08);
            border: 1px solid rgba(79,110,247,.2);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #a8b8ff;
            line-height: 1.5;
        }

        .instruction-chip .ic-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

        /* Loader overlay */
        .loader-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,17,23,.9);
            z-index: 999;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }

        .loader-overlay.show { display: flex; }

        .spinner {
            width: 52px; height: 52px;
            border: 4px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .loader-text { color: var(--muted); font-size: 14px; }

        /* Error inline */
        .alert-error {
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.3);
            color: #fca5a5;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 16px;
            display: none;
        }

        .alert-error.show { display: block; }

        /* Security note */
        .security-note {
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
    </style>
</head>
<body>

<div class="enroll-wrap">
    <!-- Header -->
    <div class="enroll-header">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
        </div>
        <div>
            <div class="brand">Verificación <span>de Identidad</span></div>
        </div>
    </div>

    <!-- Progress indicator (injected by JS) -->
    <div class="progress-bar-wrap" id="progressBarWrap"></div>

    <!-- Body -->
    <div class="enroll-body">
        @yield('content')
    </div>
</div>

<!-- Loading overlay -->
<div class="loader-overlay" id="loaderOverlay">
    <div class="spinner"></div>
    <div class="loader-text" id="loaderText">Enviando información…</div>
</div>

<script>
// Global helpers available to all enrollment screens
window.EnrollApp = {
    showLoader(msg = 'Procesando…') {
        document.getElementById('loaderText').textContent = msg;
        document.getElementById('loaderOverlay').classList.add('show');
    },
    hideLoader() {
        document.getElementById('loaderOverlay').classList.remove('show');
    },
    renderProgress(currentStep, totalSteps) {
        const labels = ['T&C', 'INE Frente', 'INE Reverso', 'Selfie', 'Listo'];
        const wrap = document.getElementById('progressBarWrap');
        if (!wrap) return;

        let dotsHtml = '';
        for (let i = 0; i < totalSteps; i++) {
            const isDone   = i < currentStep;
            const isActive = i === currentStep;
            const cls      = isDone ? 'done' : (isActive ? 'active' : '');
            dotsHtml += `<div class="step-dot ${cls}">${isDone ? '' : (i + 1)}</div>`;
            if (i < totalSteps - 1) {
                dotsHtml += `<div class="step-line ${isDone ? 'done' : ''}"></div>`;
            }
        }

        let labelsHtml = '';
        for (let i = 0; i < totalSteps; i++) {
            const isDone   = i < currentStep;
            const isActive = i === currentStep;
            const cls      = isDone ? 'done' : (isActive ? 'active' : '');
            labelsHtml += `<div class="step-label ${cls}">${labels[i] || ''}</div>`;
        }

        wrap.innerHTML = `<div class="steps-row">${dotsHtml}</div><div class="steps-labels">${labelsHtml}</div>`;
    }
};
</script>

@yield('scripts')
</body>
</html>
