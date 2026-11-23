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
    .app-content main {
        padding-top: 1rem;
    }
</style>
