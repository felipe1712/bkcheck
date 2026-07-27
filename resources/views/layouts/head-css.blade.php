<meta name="csrf-token" content="{{ csrf_token() }}">
@yield('css')
<!-- Google Fonts: Rubik & Urbanist (Manual de Identidad AvalID) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700;800&family=Urbanist:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

<!-- Layout config Js -->
<script src="{{ URL::asset('build/js/layout.js') }}"></script>
<!-- Bootstrap Css -->
<link href="{{ URL::asset('build/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Icons Css -->
<link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
<!-- App Css-->
<link href="{{ URL::asset('build/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
<!-- custom Css-->
<link href="{{ URL::asset('build/css/custom.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />

<style>
    :root {
        --avalid-dark: #141923;
        --avalid-surface: #1b2230;
        --avalid-primary: #1877f2;
        --avalid-cyan: #00a6ff;
        --avalid-slate: #56657e;
        --bs-primary: #1877f2;
        --bs-primary-rgb: 24, 119, 242;
        --bs-body-font-family: 'Urbanist', system-ui, -apple-system, sans-serif;
    }
    body {
        font-family: 'Urbanist', system-ui, -apple-system, sans-serif !important;
    }
    h1, h2, h3, h4, h5, h6, .brand-font, .card-title, .btn, .badge, .navbar-brand, .menu-link {
        font-family: 'Rubik', system-ui, -apple-system, sans-serif !important;
    }
    .brand-slogan {
        letter-spacing: 0.15em;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--avalid-slate);
    }
    .btn-primary {
        background-color: #1877f2 !important;
        border-color: #1877f2 !important;
    }
    .btn-primary:hover {
        background-color: #0a58ed !important;
        border-color: #0a58ed !important;
    }
    .bg-primary {
        background-color: #1877f2 !important;
    }
    .text-primary {
        color: #1877f2 !important;
    }
    .navbar-brand-box {
        height: 70px !important;
        display: flex !important;
        align-items: center !important;
    }
    .navbar-brand-box .logo-lg {
        display: inline-block !important;
        height: auto !important;
        line-height: 1.1 !important;
    }
    .navbar-brand-box .brand-title {
        font-size: 26px !important;
        font-family: 'Rubik', sans-serif !important;
        font-weight: 700 !important;
        letter-spacing: -0.02em !important;
        line-height: 1 !important;
    }
    .navbar-brand-box .brand-title span {
        color: #1877f2 !important;
        font-weight: 800 !important;
    }
    .navbar-brand-box .brand-slogan {
        font-size: 8.5px !important;
        font-weight: 700 !important;
        letter-spacing: 0.18em !important;
        color: #56657e !important;
        margin-top: 3px !important;
    }
    /* Ocultar logo duplicado del sidebar en modo horizontal */
    [data-layout="horizontal"] .app-menu .navbar-brand-box {
        display: none !important;
    }
</style>
