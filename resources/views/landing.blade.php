<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AvalID — Plataforma Inteligente de Due Diligence, Background Check & Scoring de Riesgo</title>
    <meta name="description" content="SaaS Multitenant de Due Diligence en México. Verificación de identidad primaria, auditoría fiscal SAT (69/69-B), listas negras PLD/FT (OFAC, ONU, PEPs) y expedientes digitales con validez legal.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #070913;
            --bg-card: rgba(15, 23, 42, 0.65);
            --bg-card-hover: rgba(30, 41, 59, 0.75);
            --border-glow: rgba(56, 189, 248, 0.2);
            --accent-blue: #1877f2;
            --accent-cyan: #06b6d4;
            --accent-teal: #0ab39c;
            --accent-purple: #8b5cf6;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        h1, h2, h3, h4, h5, h6, .brand-font {
            font-family: 'Outfit', sans-serif;
        }

        /* Ambient Glow Background Spheres */
        .ambient-glow {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            filter: blur(140px);
            opacity: 0.25;
            pointer-events: none;
            z-index: 0;
        }
        .glow-1 { top: -100px; left: -100px; background: radial-gradient(circle, var(--accent-blue), var(--accent-cyan)); }
        .glow-2 { top: 40%; right: -150px; background: radial-gradient(circle, var(--accent-purple), var(--accent-blue)); }
        .glow-3 { bottom: -100px; left: 30%; background: radial-gradient(circle, var(--accent-teal), var(--accent-cyan)); }

        /* Glassmorphism Navigation Header */
        .navbar-glass {
            background: rgba(7, 9, 19, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }

        .logo-text {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.03em;
        }
        .logo-text span { color: var(--accent-cyan); }
        .logo-badge {
            font-size: 9px;
            letter-spacing: 0.18em;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            color: #000;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            vertical-align: middle;
            margin-left: 6px;
        }

        /* Glassmorphism Cards */
        .glass-card {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .glass-card:hover {
            transform: translateY(-6px);
            border-color: rgba(6, 182, 212, 0.4);
            box-shadow: 0 20px 40px rgba(6, 182, 212, 0.12);
        }

        /* Hero Text Gradient */
        .text-gradient {
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 50%, var(--accent-cyan) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-cyan-gradient {
            background: linear-gradient(135deg, #06b6d4, #1877f2);
            color: #ffffff;
            font-weight: 700;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(6, 182, 212, 0.3);
            transition: all 0.3s ease;
        }
        .btn-cyan-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(6, 182, 212, 0.45);
            color: #ffffff;
        }

        .btn-glass-outline {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-glass-outline:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--accent-cyan);
            color: #ffffff;
        }

        /* Feature Icon Box */
        .icon-box {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.15), rgba(24, 119, 242, 0.15));
            border: 1px solid rgba(6, 182, 212, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--accent-cyan);
        }

        /* Input Styling */
        .form-glass {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-radius: 8px;
            padding: 12px 16px;
        }
        .form-glass:focus {
            background: rgba(15, 23, 42, 0.95);
            border-color: var(--accent-cyan);
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.2);
        }

        /* Tier Featured Card Highlight */
        .tier-featured {
            border: 2px solid var(--accent-cyan) !important;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.9), rgba(6, 182, 212, 0.08)) !important;
            position: relative;
        }
        .tier-badge-pop {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #06b6d4, #1877f2);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 14px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
    </style>
</head>
<body>

    <!-- Ambient Glow Effects -->
    <div class="ambient-glow glow-1"></div>
    <div class="ambient-glow glow-2"></div>
    <div class="ambient-glow glow-3"></div>

    <!-- Navigation Header -->
    <nav class="navbar navbar-expand-lg fixed-top navbar-glass py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#inicio">
                <span class="logo-text">Aval <span>ID</span></span>
                <span class="logo-badge">PRO v2</span>
            </a>
            <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <i class="ri-menu-3-line fs-24"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 fw-medium">
                    <li class="nav-item"><a class="nav-link text-white-50 px-3" href="#inicio">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50 px-3" href="#soluciones">Soluciones</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50 px-3" href="#demo">Demo Interactivo</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50 px-3" href="#niveles">Niveles de Servicio</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50 px-3" href="#cumplimiento">Cumplimiento Legal</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50 px-3" href="#contacto">Contacto</a></li>
                </ul>
                <div class="d-flex gap-2">
                    <a href="{{ route('login') }}" class="btn btn-cyan-gradient">
                        <i class="ri-user-shared-line me-1"></i> Acceso a Plataforma
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section id="inicio" class="min-vh-100 d-flex align-items-center pt-5 mt-4 position-relative">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3" style="background: rgba(6, 182, 212, 0.1); border: 1px solid rgba(6, 182, 212, 0.3);">
                        <i class="ri-shield-flash-line text-info fs-18"></i>
                        <span class="fs-13 fw-semibold text-info">Motor de Verificación & Scoring v2 en México</span>
                    </div>

                    <h1 class="display-4 fw-extrabold mb-3 leading-tight text-gradient">
                        Due Diligence, Background Check & Inteligencia de Riesgo.
                    </h1>

                    <p class="fs-18 text-muted mb-4 pe-lg-4" style="line-height: 1.6;">
                        Consolide investigaciones exhaustivas sobre Personas Físicas y Morales en segundos. Auditoría de Identidad Primaria, Sat listas 69/69-B (EFOS/EDOS), listas negras PLD/FT (OFAC, ONU, PEPs) y expedientes digitales con validez legal.
                    </p>

                    <div class="d-flex flex-wrap gap-3 mb-5">
                        <a href="#contacto" class="btn btn-cyan-gradient btn-lg fs-16">
                            <i class="ri-mail-send-line me-2"></i>Solicitar Demostración
                        </a>
                        <a href="#demo" class="btn btn-glass-outline btn-lg fs-16">
                            <i class="ri-play-circle-line me-2"></i> Probar Simulador de Riesgo
                        </a>
                    </div>

                    <!-- Live Key Metrics -->
                    <div class="row g-3 pt-3 border-top border-secondary-subtle">
                        <div class="col-6 col-md-3">
                            <div class="fs-28 fw-bold text-white mb-0">99.8%</div>
                            <div class="fs-12 text-muted">Precisión Identidad</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fs-28 fw-bold text-cyan mb-0" style="color: var(--accent-cyan);">&lt; 3 seg</div>
                            <div class="fs-12 text-muted">Tiempo Consulta</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fs-28 fw-bold text-white mb-0">100%</div>
                            <div class="fs-12 text-muted">LFPDPPP & PLD</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fs-28 fw-bold text-cyan mb-0" style="color: var(--accent-cyan);">+50K</div>
                            <div class="fs-12 text-muted">Expedientes Emitidos</div>
                        </div>
                    </div>
                </div>

                <!-- Interactive Live Risk Preview Box -->
                <div class="col-lg-5">
                    <div class="glass-card p-4 text-center">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fs-12 fw-bold text-uppercase text-muted">Vista Previa Expediente AVID-000-0001</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">EN VIVO</span>
                        </div>

                        <div class="p-3 mb-3 rounded-3" style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.06);">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fs-13 fw-bold text-white">Pablo Caudillo Martínez</span>
                                <code class="text-info fs-12">CAMP761025NV8</code>
                            </div>
                            <div class="fs-12 text-muted text-start">Persona Física · Due Diligence Comercial</div>
                        </div>

                        <!-- Mini SVG Gauge -->
                        <div class="position-relative my-2 d-inline-block" style="width: 200px; height: 115px;">
                            <svg viewBox="0 0 200 120" width="100%" height="100%">
                                <path d="M 20 100 A 80 80 0 0 1 34.64 52.98" fill="none" stroke="#d32f2f" stroke-width="16" />
                                <path d="M 34.64 52.98 A 80 80 0 0 1 75.28 23.95" fill="none" stroke="#f06548" stroke-width="16" />
                                <path d="M 75.28 23.95 A 80 80 0 0 1 124.72 23.95" fill="none" stroke="#f7b84b" stroke-width="16" />
                                <path d="M 124.72 23.95 A 80 80 0 0 1 165.36 52.98" fill="none" stroke="#84c835" stroke-width="16" />
                                <path d="M 165.36 52.98 A 80 80 0 0 1 180 100" fill="none" stroke="#0ab39c" stroke-width="16" />
                                <circle cx="100" cy="100" r="7" fill="#ffffff" />
                                <g transform="rotate(162, 100, 100)">
                                    <line x1="100" y1="100" x2="32" y2="100" stroke="#ffffff" stroke-width="4" stroke-linecap="round" />
                                    <polygon points="32,97 22,100 32,103" fill="#ffffff" />
                                </g>
                            </svg>
                        </div>

                        <h3 class="fw-extrabold mb-0 text-cyan" style="color: var(--accent-cyan);">90%</h3>
                        <span class="badge bg-success fs-12 px-3 py-1 text-uppercase fw-bold mb-3">Confiabilidad Muy Alta</span>

                        <!-- Recommendation Banner -->
                        <div class="p-2 rounded-3 text-start mb-3" style="background: rgba(10, 179, 156, 0.15); border-left: 3px solid var(--accent-teal);">
                            <div class="fs-12 fw-bold text-white">🟢 Proceder sin observaciones</div>
                            <div class="fs-11 text-muted">Credenciales verificadas y expediente limpio de sanciones.</div>
                        </div>

                        <a href="#contacto" class="btn btn-cyan-gradient w-100 btn-sm py-2">Probar con tus Candidatos</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SOLUCIONES Y MÓDULOS DE VERIFICACIÓN -->
    <section id="soluciones" class="py-5 position-relative">
        <div class="container py-5">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="fs-13 fw-bold text-info text-uppercase tracking-wider">Módulos de Auditoría Integra</span>
                <h2 class="display-5 fw-extrabold mt-2 text-gradient">Fuentes Oficiales y Cobertura 360°</h2>
                <p class="text-muted fs-16">Nuestra arquitectura de conectores consulta y consolida directamente las bases de datos gubernamentales e internacionales más críticas.</p>
            </div>

            <div class="row g-4">
                <!-- Feature 1 -->
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card p-4 h-100">
                        <div class="icon-box mb-3"><i class="ri-user-vcard-line"></i></div>
                        <h4 class="fw-bold fs-18 text-white mb-2">Identidad Primaria & Biometría</h4>
                        <p class="text-muted fs-14 mb-3">Lectura OCR de INE frontal y reverso, validación de Padrón Electoral, RENAPO CURP y cotejo de certeza biométrica facial (INE vs Selfie).</p>
                        <span class="badge bg-dark border border-secondary text-info fs-11">INE · RENAPO · FACIAL</span>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card p-4 h-100">
                        <div class="icon-box mb-3"><i class="ri-bank-card-line"></i></div>
                        <h4 class="fw-bold fs-18 text-white mb-2">Auditoría Fiscal & EFOS / EDOS</h4>
                        <p class="text-muted fs-14 mb-3">Validación de estatus de RFC en tiempo real ante el SAT, certificados CSD/e-Firma y monitoreo de listas 69 y 69-B (operaciones simuladas).</p>
                        <span class="badge bg-dark border border-secondary text-info fs-11">SAT 69 · 69-B · CSD</span>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card p-4 h-100">
                        <div class="icon-box mb-3"><i class="ri-shield-cross-line"></i></div>
                        <h4 class="fw-bold fs-18 text-white mb-2">Prevención de Lavado de Dinero (PLD)</h4>
                        <p class="text-muted fs-14 mb-3">Búsqueda nombrada en OFAC SDN (EE.UU.), Resoluciones de la ONU, listas consolidadas de la UE, Personas Bloqueadas (LPB CNBV/UIF) y PEPs.</p>
                        <span class="badge bg-dark border border-secondary text-info fs-11">OFAC · ONU · PEPs</span>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card p-4 h-100">
                        <div class="icon-box mb-3"><i class="ri-scales-3-line"></i></div>
                        <h4 class="fw-bold fs-18 text-white mb-2">Antecedentes Judiciales & Mercantiles</h4>
                        <p class="text-muted fs-14 mb-3">Rastreo de boletines judiciales a nivel local y federal, consultas al Registro Público de Comercio (SIGER) y marcas registradas ante el IMPI.</p>
                        <span class="badge bg-dark border border-secondary text-info fs-11">BOLETÍN JUDICIAL · SIGER</span>
                    </div>
                </div>

                <!-- Feature 5 -->
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card p-4 h-100">
                        <div class="icon-box mb-3"><i class="ri-building-4-line"></i></div>
                        <h4 class="fw-bold fs-18 text-white mb-2">Geolocalización & INEGI DENUE</h4>
                        <p class="text-muted fs-14 mb-3">Directorio Nacional de Unidades Económicas (DENUE). Verificación de domicilio físico, actividad SCIAN, estrato de empleados y mapas oficiales.</p>
                        <span class="badge bg-dark border border-secondary text-info fs-11">INEGI · SCIAN · MAPS</span>
                    </div>
                </div>

                <!-- Feature 6 -->
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card p-4 h-100">
                        <div class="icon-box mb-3"><i class="ri-file-lock-line"></i></div>
                        <h4 class="fw-bold fs-18 text-white mb-2">Consistencia & Criptografía Inmutable</h4>
                        <p class="text-muted fs-14 mb-3">Motor de coherencia cruzada entre nombres, RFC, CURP y fechas de nacimiento. Bitácora inmutable de auditoría con sello SHA-256 por consulta.</p>
                        <span class="badge bg-dark border border-secondary text-info fs-11">AUDIT LOG · LFPDPPP</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- DEMO INTERACTIVO / SIMULADOR DE RIESGO -->
    <section id="demo" class="py-5 position-relative">
        <div class="container py-5">
            <div class="glass-card p-4 p-md-5">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <span class="fs-13 fw-bold text-info text-uppercase tracking-wider">Simulador en Tiempo Real</span>
                        <h2 class="display-6 fw-extrabold mt-2 text-white">Experimenta la Lógica del Multímetro AvalID</h2>
                        <p class="text-muted fs-15 mb-4">
                            Mueve el control deslizante para simular el puntaje global de un candidato y observa cómo se recalculan automáticamente los 4 índices independientes y la recomendación de acción.
                        </p>

                        <!-- Range Slider Input -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="fs-14 fw-bold text-white">Ajustar Puntaje Simulado:</label>
                                <span id="sliderValueText" class="fs-20 fw-extrabold text-cyan" style="color: var(--accent-cyan);">85%</span>
                            </div>
                            <input type="range" class="form-range" id="scoreSimSlider" min="0" max="100" value="85" style="cursor: pointer;">
                        </div>

                        <div class="p-3 rounded-3 mb-3" style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.08);">
                            <div class="fs-12 fw-bold text-uppercase text-muted mb-1">Recomendación Ejecutiva Generada:</div>
                            <div id="simRecTitle" class="fs-15 fw-bold text-white mb-1">🟢 Proceder sin observaciones</div>
                            <div id="simRecDesc" class="fs-13 text-muted">El sujeto presenta credenciales de identidad verificadas y expediente limpio.</div>
                        </div>
                    </div>

                    <!-- Live Dynamic Gauges & Multi-index Cards -->
                    <div class="col-lg-6">
                        <div class="p-4 rounded-4 text-center" style="background: rgba(7, 9, 19, 0.9); border: 1px solid rgba(255,255,255,0.1);">
                            <!-- Dynamic Gauge SVG -->
                            <div class="position-relative mb-2 d-inline-block" style="width: 220px; height: 125px;">
                                <svg viewBox="0 0 200 120" width="100%" height="100%">
                                    <path d="M 20 100 A 80 80 0 0 1 34.64 52.98" fill="none" stroke="#d32f2f" stroke-width="18" />
                                    <path d="M 34.64 52.98 A 80 80 0 0 1 75.28 23.95" fill="none" stroke="#f06548" stroke-width="18" />
                                    <path d="M 75.28 23.95 A 80 80 0 0 1 124.72 23.95" fill="none" stroke="#f7b84b" stroke-width="18" />
                                    <path d="M 124.72 23.95 A 80 80 0 0 1 165.36 52.98" fill="none" stroke="#84c835" stroke-width="18" />
                                    <path d="M 165.36 52.98 A 80 80 0 0 1 180 100" fill="none" stroke="#0ab39c" stroke-width="18" />
                                    <circle cx="100" cy="100" r="7" fill="#ffffff" />
                                    <g id="simNeedleGroup" transform="rotate(153, 100, 100)">
                                        <line x1="100" y1="100" x2="32" y2="100" stroke="#ffffff" stroke-width="4" stroke-linecap="round" />
                                        <polygon points="32,97 22,100 32,103" fill="#ffffff" />
                                    </g>
                                </svg>
                            </div>

                            <h3 id="simScoreText" class="display-6 fw-bold mb-1" style="color: #0ab39c;">85%</h3>
                            <span id="simRiskBadge" class="badge bg-success fs-12 px-3 py-1 text-uppercase fw-bold mb-4">Riesgo Bajo / Confiable</span>

                            <!-- 4 Indices Progress Bars -->
                            <div class="text-start px-2">
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between fs-12 text-muted mb-1">
                                        <span>• Identidad Primaria:</span>
                                        <strong id="idxIdentidadText" class="text-white">90%</strong>
                                    </div>
                                    <div class="progress" style="height: 6px; background: rgba(255,255,255,0.1);">
                                        <div id="idxIdentidadBar" class="progress-bar bg-success" style="width: 90%;"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between fs-12 text-muted mb-1">
                                        <span>• Cumplimiento PLD / SAT:</span>
                                        <strong id="idxCumplimientoText" class="text-white">85%</strong>
                                    </div>
                                    <div class="progress" style="height: 6px; background: rgba(255,255,255,0.1);">
                                        <div id="idxCumplimientoBar" class="progress-bar bg-success" style="width: 85%;"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between fs-12 text-muted mb-1">
                                        <span>• Consistencia Documental:</span>
                                        <strong id="idxConsistenciaText" class="text-white">100%</strong>
                                    </div>
                                    <div class="progress" style="height: 6px; background: rgba(255,255,255,0.1);">
                                        <div id="idxConsistenciaBar" class="progress-bar bg-info" style="width: 100%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- NIVELES DE SERVICIO / PRODUCT TIERS -->
    <section id="niveles" class="py-5 position-relative">
        <div class="container py-5">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="fs-13 fw-bold text-info text-uppercase tracking-wider">Escalabilidad de Producto</span>
                <h2 class="display-5 fw-extrabold mt-2 text-gradient">Planes Adaptables a tu Empresa</h2>
                <p class="text-muted fs-16">Una misma infraestructura base que escala según la profundidad de auditoría requerida.</p>
            </div>

            <div class="row g-4 align-items-stretch">
                <!-- Tier 1 -->
                <div class="col-md-6 col-lg-3">
                    <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="fs-12 fw-bold text-uppercase text-muted">Nivel 1</span>
                            <h3 class="fw-bold text-white fs-20 mt-1 mb-3">Identidad Base</h3>
                            <ul class="list-unstyled fs-13 text-muted mb-4 space-y-2">
                                <li class="mb-2"><i class="ri-check-line text-info me-2"></i>Validación RFC (SAT)</li>
                                <li class="mb-2"><i class="ri-check-line text-info me-2"></i>RENAPO CURP</li>
                                <li class="mb-2"><i class="ri-check-line text-info me-2"></i>OCR INE Frente / Reverso</li>
                                <li class="mb-2"><i class="ri-check-line text-info me-2"></i>Padrón Electoral INE</li>
                            </ul>
                        </div>
                        <a href="#contacto" class="btn btn-glass-outline w-100">Solicitar Nivel 1</a>
                    </div>
                </div>

                <!-- Tier 2 -->
                <div class="col-md-6 col-lg-3">
                    <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="fs-12 fw-bold text-uppercase text-muted">Nivel 2</span>
                            <h3 class="fw-bold text-white fs-20 mt-1 mb-3">Verificación Plus</h3>
                            <ul class="list-unstyled fs-13 text-muted mb-4 space-y-2">
                                <li class="mb-2"><i class="ri-check-line text-info me-2"></i>Todo lo de Nivel 1</li>
                                <li class="mb-2"><i class="ri-check-line text-info me-2"></i>Certificados CSD / e-Firma</li>
                                <li class="mb-2"><i class="ri-check-line text-info me-2"></i>SAT Listas 69 / 69-B (EFOS)</li>
                                <li class="mb-2"><i class="ri-check-line text-info me-2"></i>Biometría Facial (Selfie)</li>
                            </ul>
                        </div>
                        <a href="#contacto" class="btn btn-glass-outline w-100">Solicitar Nivel 2</a>
                    </div>
                </div>

                <!-- Tier 3 (Featured) -->
                <div class="col-md-6 col-lg-3">
                    <div class="glass-card tier-featured p-4 h-100 d-flex flex-column justify-content-between">
                        <span class="tier-badge-pop">Más Popular</span>
                        <div>
                            <span class="fs-12 fw-bold text-uppercase text-info">Nivel 3</span>
                            <h3 class="fw-bold text-white fs-20 mt-1 mb-3">Due Diligence</h3>
                            <ul class="list-unstyled fs-13 text-muted mb-4 space-y-2">
                                <li class="mb-2"><i class="ri-check-line text-cyan me-2" style="color: var(--accent-cyan);"></i>Todo lo de Nivel 2</li>
                                <li class="mb-2"><i class="ri-check-line text-cyan me-2" style="color: var(--accent-cyan);"></i>Boletín Judicial & Litigios</li>
                                <li class="mb-2"><i class="ri-check-line text-cyan me-2" style="color: var(--accent-cyan);"></i>SIGER & Registro Mercantil</li>
                                <li class="mb-2"><i class="ri-check-line text-cyan me-2" style="color: var(--accent-cyan);"></i>Marcas IMPI & DENUE INEGI</li>
                                <li class="mb-2"><i class="ri-check-line text-cyan me-2" style="color: var(--accent-cyan);"></i>PLD / OFAC / ONU / PEPs</li>
                            </ul>
                        </div>
                        <a href="#contacto" class="btn btn-cyan-gradient w-100">Solicitar Due Diligence</a>
                    </div>
                </div>

                <!-- Tier 4 -->
                <div class="col-md-6 col-lg-3">
                    <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="fs-12 fw-bold text-uppercase text-purple" style="color: var(--accent-purple);">Nivel 4</span>
                            <h3 class="fw-bold text-white fs-20 mt-1 mb-3">Seguridad Reforzada</h3>
                            <ul class="list-unstyled fs-13 text-muted mb-4 space-y-2">
                                <li class="mb-2"><i class="ri-check-line text-purple me-2" style="color: var(--accent-purple);"></i>Todo lo de Nivel 3</li>
                                <li class="mb-2"><i class="ri-check-line text-purple me-2" style="color: var(--accent-purple);"></i>Adverse Media & Noticioso</li>
                                <li class="mb-2"><i class="ri-check-line text-purple me-2" style="color: var(--accent-purple);"></i>Huella Digital & OSINT</li>
                                <li class="mb-2"><i class="ri-check-line text-purple me-2" style="color: var(--accent-purple);"></i>Monitoreo Activo 24/7</li>
                            </ul>
                        </div>
                        <a href="#contacto" class="btn btn-glass-outline w-100">Solicitar Nivel 4</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CUMPLIMIENTO LEGAL & SEGURIDAD -->
    <section id="cumplimiento" class="py-5 position-relative">
        <div class="container py-5">
            <div class="glass-card p-4 p-md-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <span class="fs-13 fw-bold text-info text-uppercase tracking-wider">Cumplimiento Legal Obligatorio</span>
                        <h2 class="display-6 fw-extrabold text-white mt-1 mb-3">Alineación LFPDPPP & Garantía de Privacidad</h2>
                        <p class="text-muted fs-15 mb-0" style="line-height: 1.6;">
                            AvalID opera estrictamente bajo el marco de la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP). Cada investigación requiere captura explícita de consentimiento con catálogo cerrado de finalidades legales, resguardo inmutable de auditoría y cifrado AES-256 en reposo.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <div class="p-3 rounded-3 d-inline-block text-start" style="background: rgba(6, 182, 212, 0.1); border: 1px solid rgba(6, 182, 212, 0.3);">
                            <div class="fs-12 fw-bold text-info"><i class="ri-lock-password-line me-1"></i> Cifrado de Grado Bancario</div>
                            <div class="fs-11 text-muted mt-1">Global Scope Multitenant Aislado por Tenant</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FORMULARIO DE CONTACTO / SOLICITUD DE DEMO -->
    <section id="contacto" class="py-5 position-relative">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="glass-card p-4 p-md-5">
                        <div class="text-center mb-4">
                            <span class="fs-13 fw-bold text-info text-uppercase tracking-wider">Atención a Clientes & Ventas</span>
                            <h2 class="display-6 fw-extrabold text-white mt-1">Solicita una Demostración de AvalID</h2>
                            <p class="text-muted fs-15">Déjanos tus datos y un consultor especialista te mostrará cómo integrar AvalID en tu organización.</p>
                        </div>

                        <!-- Feedback Alert -->
                        <div id="contactAlert" class="alert d-none fs-14 mb-4"></div>

                        @if(session('contact_success'))
                            <div class="alert alert-success fs-14 mb-4">
                                {{ session('contact_success') }}
                            </div>
                        @endif

                        <form id="contactForm" action="{{ route('contacto.store') }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fs-13 fw-bold text-white">Nombre Completo *</label>
                                    <input type="text" name="name" class="form-control form-glass" placeholder="Ej. Lic. Roberto Gómez" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-13 fw-bold text-white">Correo Corporativo *</label>
                                    <input type="email" name="email" class="form-control form-glass" placeholder="rgomez@empresa.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-13 fw-bold text-white">Empresa / Despacho</label>
                                    <input type="text" name="company" class="form-control form-glass" placeholder="Consultores Alfa S.C.">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-13 fw-bold text-white">Teléfono de Contacto</label>
                                    <input type="text" name="phone" class="form-control form-glass" placeholder="+52 55 1234 5678">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fs-13 fw-bold text-white">Nivel de Servicio de Interés</label>
                                    <select name="service_tier" class="form-select form-glass">
                                        <option value="due_diligence" selected>Nivel 3: Due Diligence Corporativo (Recomendado)</option>
                                        <option value="base">Nivel 1: Identidad Base</option>
                                        <option value="plus">Nivel 2: Verificación Plus</option>
                                        <option value="seguridad_reforzada">Nivel 4: Seguridad Reforzada & OSINT</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fs-13 fw-bold text-white">Mensaje o Requerimientos *</label>
                                    <textarea name="message" rows="4" class="form-control form-glass" placeholder="Describe brevemente tus requerimientos o número aproximado de investigaciones mensuales..." required></textarea>
                                </div>
                                <div class="col-12 mt-4 text-center">
                                    <button type="submit" id="btnSubmitContact" class="btn btn-cyan-gradient btn-lg w-100 py-3">
                                        <i class="ri-send-plane-fill me-2"></i>Enviar Solicitud de Demostración
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="py-4 border-top border-secondary-subtle position-relative" style="background: #04060d;">
        <div class="container text-center">
            <div class="mb-3">
                <a class="logo-text text-white text-decoration-none fs-22" href="#inicio">
                    Aval <span>ID</span>
                </a>
            </div>
            <p class="fs-13 text-muted mb-2">
                Plataforma SaaS Multitenant de Background Check & Due Diligence en México.
            </p>
            <p class="fs-12 text-muted mb-0">
                &copy; {{ date('Y') }} AvalID. La Confianza de tu Gente. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Interactive Live Demo & AJAX Contact Form Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Interactive Risk Score Simulator Logic
            const slider = document.getElementById('scoreSimSlider');
            const sliderValueText = document.getElementById('sliderValueText');
            const simScoreText = document.getElementById('simScoreText');
            const simRiskBadge = document.getElementById('simRiskBadge');
            const simNeedleGroup = document.getElementById('simNeedleGroup');
            const simRecTitle = document.getElementById('simRecTitle');
            const simRecDesc = document.getElementById('simRecDesc');

            const idxIdentidadText = document.getElementById('idxIdentidadText');
            const idxIdentidadBar = document.getElementById('idxIdentidadBar');
            const idxCumplimientoText = document.getElementById('idxCumplimientoText');
            const idxCumplimientoBar = document.getElementById('idxCumplimientoBar');
            const idxConsistenciaText = document.getElementById('idxConsistenciaText');
            const idxConsistenciaBar = document.getElementById('idxConsistenciaBar');

            if (slider) {
                slider.addEventListener('input', function() {
                    const score = parseInt(this.value);
                    sliderValueText.textContent = score + '%';
                    simScoreText.textContent = score + '%';

                    // Calculate Needle Rotation Angle (0% -> 0deg, 100% -> 180deg)
                    const angle = score * 1.8;
                    simNeedleGroup.setAttribute('transform', `rotate(${angle}, 100, 100)`);

                    // Update 4 Indices simulation
                    const idScore = Math.min(100, Math.max(0, score + 5));
                    const cumpScore = Math.min(100, Math.max(0, score - 5));
                    const consScore = score >= 50 ? 100 : 60;

                    idxIdentidadText.textContent = idScore + '%';
                    idxIdentidadBar.style.width = idScore + '%';
                    idxCumplimientoText.textContent = cumpScore + '%';
                    idxCumplimientoBar.style.width = cumpScore + '%';
                    idxConsistenciaText.textContent = consScore + '%';
                    idxConsistenciaBar.style.width = consScore + '%';

                    // Update Status Colors & Recommendation
                    if (score >= 90) {
                        simScoreText.style.color = '#0ab39c';
                        simRiskBadge.className = 'badge bg-success fs-12 px-3 py-1 text-uppercase fw-bold mb-4';
                        simRiskBadge.textContent = 'Riesgo Mínimo / Confiable';
                        simRecTitle.textContent = '🟢 Proceder sin observaciones';
                        simRecDesc.textContent = 'El sujeto presenta credenciales de identidad verificadas y expediente limpio.';
                    } else if (score >= 70) {
                        simScoreText.style.color = '#f7b84b';
                        simRiskBadge.className = 'badge bg-warning text-dark fs-12 px-3 py-1 text-uppercase fw-bold mb-4';
                        simRiskBadge.textContent = 'Riesgo Moderado';
                        simRecTitle.textContent = '🟡 Validar manualmente antes de proceder';
                        simRecDesc.textContent = 'Se detectaron observaciones menores o fuentes pendientes de revisión.';
                    } else {
                        simScoreText.style.color = '#d32f2f';
                        simRiskBadge.className = 'badge bg-danger fs-12 px-3 py-1 text-uppercase fw-bold mb-4';
                        simRiskBadge.textContent = 'Alto Riesgo Detectado';
                        simRecTitle.textContent = '🔴 No se recomienda proceder sin autorización';
                        simRecDesc.textContent = 'El expediente presenta alertas de alta severidad o boletinación en sanciones.';
                    }
                });
            }

            // AJAX Contact Form Handler
            const contactForm = document.getElementById('contactForm');
            const contactAlert = document.getElementById('contactAlert');
            const btnSubmit = document.getElementById('btnSubmitContact');

            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i class="ri-loader-4-line ri-spin me-2"></i>Enviando Solicitud...';

                    const formData = new FormData(this);

                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i class="ri-send-plane-fill me-2"></i>Enviar Solicitud de Demostración';

                        contactAlert.classList.remove('d-none', 'alert-danger');
                        contactAlert.classList.add('alert-success');
                        contactAlert.textContent = data.message || '¡Gracias por contactarnos! Tu mensaje fue recibido.';
                        contactForm.reset();
                    })
                    .catch(err => {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i class="ri-send-plane-fill me-2"></i>Enviar Solicitud de Demostración';

                        contactAlert.classList.remove('d-none', 'alert-success');
                        contactAlert.classList.add('alert-danger');
                        contactAlert.textContent = 'Ocurrió un error al enviar el formulario. Por favor intenta de nuevo.';
                    });
                });
            }
        });
    </script>
</body>
</html>
