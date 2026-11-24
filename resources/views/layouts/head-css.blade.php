<!-- Animation css -->
<link rel="stylesheet" href="{{ asset('assets/vendor/animation/animate.min.css') }}">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400..900&display=swap" rel="stylesheet">

<!-- Tabler & Remix Icons -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/tabler-icons/tabler-icons.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css">

<!-- Bootstrap css -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">

<!-- Simplebar css -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/simplebar/simplebar.css') }}">

@yield('css')

@vite(['public/assets/scss/style.scss'])

<style>
    /* Tema claro uniforme para AvalID Porcupine */
    :root, [data-theme="light"] {
        --bs-body-bg: #f8fafc;
        --bs-body-color: #1e293b;
        --bs-card-bg: #ffffff;
        --bs-primary: #1d4ed8;
        --bs-primary-rgb: 29, 78, 216;
        --primary: #1d4ed8;
        --primary-color: #1d4ed8;
    }
    body {
        background-color: #f8fafc !important;
        color: #1e293b;
        font-family: 'Golos Text', sans-serif;
    }
    nav.vertical-sidebar, nav {
        background-color: #ffffff !important;
        border-right: 1px solid #e2e8f0 !important;
    }
    header.header-main {
        background-color: #ffffff !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }
    .card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }

    /* 1. Margen superior amplio para evitar que el header fijo tape el título de página */
    .app-content main {
        padding-top: 5.5rem !important;
        padding-bottom: 4.5rem !important;
        padding-left: 1.25rem;
        padding-right: 1.25rem;
    }

    /* 2. Títulos del menú lateral limpios (sin recuadro de fondo) y en azul oscuro */
    nav .app-nav .main-nav .menu-title,
    nav .app-nav .main-nav .menu-title span {
        background: transparent !important;
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
        color: #1e3a8a !important;
        font-weight: 700 !important;
        font-size: 0.72rem !important;
        letter-spacing: 0.08em !important;
        padding-left: 0.25rem !important;
    }
    nav .app-nav .main-nav li a {
        color: #334155 !important;
        border-radius: 0.5rem;
        transition: all 0.15s ease;
    }
    nav .app-nav .main-nav li a:hover,
    nav .app-nav .main-nav li.active > a {
        background-color: #1e3a8a !important;
        color: #ffffff !important;
    }
    nav .app-nav .main-nav li a:hover i,
    nav .app-nav .main-nav li.active > a i {
        color: #ffffff !important;
    }

    /* 3. Botones y acentos en azul oscuro con letra clara */
    .btn-primary,
    .bg-primary {
        background-color: #1e3a8a !important;
        border-color: #1e3a8a !important;
        color: #ffffff !important;
    }
    .btn-primary:hover,
    .btn-primary:focus {
        background-color: #1d4ed8 !important;
        border-color: #1d4ed8 !important;
        color: #ffffff !important;
    }
    .text-primary {
        color: #1e3a8a !important;
    }
    a {
        color: #1d4ed8;
    }
</style>
