<!-- Header Section starts -->
<header class="header-main">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-6 col-sm-5 d-flex align-items-center header-left p-0">
                <span class="header-toggle me-3">
                    <i class="ti ti-menu-2"></i>
                </span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 d-none d-sm-inline-block">
                    <i class="ti ti-radar me-1"></i> Porcupine v3
                </span>
            </div>

            <div class="col-6 col-sm-7 d-flex align-items-center justify-content-end header-right p-0">
                <ul class="d-flex align-items-center mb-0 list-unstyled">
                    <li class="header-profile">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="page-header-user-dropdown"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="h-35 w-35 d-flex-center b-r-50 bg-primary-subtle text-primary me-2">
                                <i class="ti ti-user f-s-18"></i>
                            </span>
                            <span class="text-start d-none d-md-block">
                                <span class="d-block fw-semibold text-dark f-s-13">{{ Auth::user()->name }}</span>
                                <span class="d-block text-muted f-s-11">
                                    @if(Auth::user()->hasRole('super_admin'))
                                        Super Admin
                                    @elseif(Auth::user()->hasRole('tenant_admin'))
                                        Administrador de Cliente
                                    @elseif(Auth::user()->hasRole('investigador'))
                                        Investigador
                                    @else
                                        Usuario
                                    @endif
                                </span>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end border shadow-sm">
                            <h6 class="dropdown-header">¡Bienvenido, {{ Auth::user()->name }}!</h6>
                            <a class="dropdown-item text-danger" href="javascript:void(0);"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="ti ti-logout me-1"></i> Cerrar Sesión
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
<!-- Header Section ends -->
