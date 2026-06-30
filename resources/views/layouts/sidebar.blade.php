<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="{{ route('root') }}" class="logo logo-dark">
            <span class="logo-sm">
                <span class="fs-16 fw-bold text-primary">A</span>
            </span>
            <span class="logo-lg">
                <span class="fs-20 fw-bold text-primary">ATLAS</span>
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ route('root') }}" class="logo logo-light">
            <span class="logo-sm">
                <span class="fs-16 fw-bold text-white">A</span>
            </span>
            <span class="logo-lg">
                <span class="fs-20 fw-bold text-white">ATLAS</span>
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div class="dropdown sidebar-user m-1 rounded">
        <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown" data-bs-toggle="dropdown"
            aria-haspopup="true" aria-expanded="false">
            <span class="d-flex align-items-center gap-2">
                <div class="rounded-circle header-profile-user bg-light text-primary d-flex align-items-center justify-content-center">
                    <i class="ri-user-line fs-16"></i>
                </div>
                <span class="text-start">
                    <span class="d-block fw-medium sidebar-user-name-text">{{ Auth::user()->name }}</span>
                    <span class="d-block fs-14 sidebar-user-name-sub-text">
                        <i class="ri ri-circle-fill fs-10 text-success align-baseline"></i> 
                        <span class="align-middle">En línea</span>
                    </span>
                </span>
            </span>
        </button>
        <div class="dropdown-menu dropdown-menu-end">
            <!-- item-->
            <h6 class="dropdown-header">¡Bienvenido {{ Auth::user()->name }}!</h6>
            <a class="dropdown-item" href="javascript:void(0);"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i> 
                <span>Cerrar Sesión</span>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span>Due Diligence</span></li>

                @role('super_admin')
                <!-- Super Admin Menu -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('superadmin.dashboard') }}">
                        <i class="ri-dashboard-2-line"></i> <span>Dashboard Global</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('superadmin.tenants.index') }}">
                        <i class="ri-git-repository-private-line"></i> <span>Clientes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('superadmin.users.index') }}">
                        <i class="ri-group-line"></i> <span>Usuarios</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('superadmin.activity-logs') }}">
                        <i class="ri-file-list-3-line"></i> <span>Bitácora de Actividad</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('superadmin.audit-logs') }}">
                        <i class="ri-shield-keyhole-line"></i> <span>Auditoría de Consultas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('superadmin.api-logs') }}">
                        <i class="ri-code-s-slash-line"></i> <span>Log de APIs</span>
                    </a>
                </li>
                @endrole

                @hasanyrole('tenant_admin|investigador')
                <!-- Tenant Menu -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('tenant.dashboard') }}">
                        <i class="ri-dashboard-line"></i> <span>Inicio</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('tenant.projects.index') }}">
                        <i class="ri-folder-open-line"></i> <span>Proyectos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('tenant.subjects.index') }}">
                        <i class="ri-user-search-line"></i> <span>Sujetos (Consultas)</span>
                    </a>
                </li>
                @role('tenant_admin')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('tenant.users.index') }}">
                        <i class="ri-group-line"></i> <span>Usuarios</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('tenant.consumption') }}">
                        <i class="ri-money-dollar-circle-line"></i> <span>Consultas / Consumo</span>
                    </a>
                </li>
                @endrole
                @endhasanyrole
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
