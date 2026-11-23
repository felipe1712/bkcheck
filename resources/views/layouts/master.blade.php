<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    @include('layouts.head')
    @include('layouts.head-css')
</head>

<body class="ltr">
<div class="app-wrapper">
    <!-- Loader -->
    <div class="loader-wrapper">
        <div class="loader_16"></div>
    </div>

    <!-- Sidebar -->
    @include('layouts.sidebar')

    <div class="app-content">
        <!-- Header -->
        @include('layouts.topbar')

        <!-- Main Content -->
        <main>
            <div class="container-fluid">
                @yield('content')
                @yield('main-content')
            </div>
        </main>
    </div>

    <!-- Tap on top -->
    <div class="go-top">
        <span class="progress-value">
            <i class="ti ti-arrow-up"></i>
        </span>
    </div>

    <!-- Footer -->
    @include('layouts.footer')
</div>

@include('layouts.vendor-scripts')
</body>

</html>
