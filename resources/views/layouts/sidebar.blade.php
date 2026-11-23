<!-- Menu Navigation starts -->
<nav class="vertical-sidebar light-sidebar">
    <div class="app-logo">
        <a class="logo d-inline-block text-decoration-none" href="{{ route('root') }}">
            <span class="fs-20 fw-bold" style="color: #0f172a;">Aval <span style="color: #1877f2;">ID</span></span>
            <span class="d-block text-muted" style="font-size: 9px; letter-spacing: 0.08em;">PORCUPINE · DUE DILIGENCE</span>
        </a>

        <span class="bg-light-primary toggle-semi-nav">
            <i class="ti ti-chevrons-right f-s-20"></i>
        </span>
    </div>

    <div class="app-nav" id="app-simple-bar">
        <ul class="main-nav p-0 mt-2">
            <li class="menu-title">
                <span>Due Diligence</span>
            </li>

            @role('super_admin')
            <li class="no-sub">
                <a href="{{ route('superadmin.dashboard') }}">
                    <i class="ti ti-dashboard"></i> Dashboard Global
                </a>
            </li>
            <li class="no-sub">
                <a href="{{ route('superadmin.tenants.index') }}">
                    <i class="ti ti-building"></i> Clientes
                </a>
            </li>
            <li class="no-sub">
                <a href="{{ route('superadmin.users.index') }}">
                    <i class="ti ti-users"></i> Usuarios
                </a>
            </li>
            <li class="no-sub">
                <a href="{{ route('superadmin.activity-logs') }}">
                    <i class="ti ti-list-details"></i> Bitácora de Actividad
                </a>
            </li>
            <li class="no-sub">
                <a href="{{ route('superadmin.audit-logs') }}">
                    <i class="ti ti-shield-lock"></i> Auditoría de Consultas
                </a>
            </li>
            <li class="no-sub">
                <a href="{{ route('superadmin.api-logs') }}">
                    <i class="ti ti-code"></i> Log de APIs
                </a>
            </li>
            @endrole

            @hasanyrole('tenant_admin|investigador')
            <li class="no-sub">
                <a href="{{ route('tenant.dashboard') }}">
                    <i class="ti ti-home"></i> Inicio
                </a>
            </li>
            <li class="no-sub">
                <a href="{{ route('tenant.projects.index') }}">
                    <i class="ti ti-folders"></i> Proyectos
                </a>
            </li>
            <li class="no-sub">
                <a href="{{ route('tenant.subjects.index') }}">
                    <i class="ti ti-user-search"></i> Sujetos (Consultas)
                </a>
            </li>

            @role('tenant_admin')
            <li class="menu-title">
                <span>Administración</span>
            </li>
            <li class="no-sub">
                <a href="{{ route('tenant.users.index') }}">
                    <i class="ti ti-users"></i> Usuarios
                </a>
            </li>
            <li class="no-sub">
                <a href="{{ route('tenant.consumption') }}">
                    <i class="ti ti-chart-bar"></i> Consultas / Consumo
                </a>
            </li>
            <li class="no-sub">
                <a href="{{ route('tenant.settings') }}">
                    <i class="ti ti-settings"></i> Configuración
                </a>
            </li>
            @endrole
            @endhasanyrole
        </ul>
    </div>
</nav>
<!-- Menu Navigation ends -->
