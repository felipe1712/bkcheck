<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="{{ route('root') }}" class="logo logo-dark">
                        <span class="logo-sm">
                            <span class="fs-16 fw-bold text-primary">A</span>
                        </span>
                        <span class="logo-lg">
                            <img src="{{ asset('images/avalid-logo.png') }}" alt="AvalID" style="height:32px;">
                        </span>
                    </a>

                    <a href="{{ route('root') }}" class="logo logo-light">
                        <span class="logo-sm">
                            <span class="fs-16 fw-bold text-white">A</span>
                        </span>
                        <span class="logo-lg">
                            <img src="{{ asset('images/avalid-logo.png') }}" alt="AvalID" style="height:32px; filter: brightness(0) invert(1);">
                        </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger material-shadow-none" id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
            </div>

            <div class="d-flex align-items-center">
                <!-- User Profile Dropdown -->
                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <div class="rounded-circle header-profile-user bg-light text-primary d-flex align-items-center justify-content-center">
                                <i class="ri-user-line fs-16"></i>
                            </div>
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ Auth::user()->name }}</span>
                                <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text text-capitalize">
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
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <h6 class="dropdown-header">¡Bienvenido!</h6>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> 
                            <span>Cerrar Sesión</span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
