<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    @include('layouts.head')
    @include('layouts.head-css')
</head>

<body class="ltr bg-light">
    <div class="app-wrapper d-block min-vh-100">
        @yield('content')
        @yield('main-content')
    </div>

    @include('layouts.vendor-scripts')
</body>

</html>
