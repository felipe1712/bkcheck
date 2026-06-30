<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\ApiUsage;
use App\Models\AuditLog;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin']);
    }

    /**
     * Display a global dashboard with API usage statistics.
     */
    public function dashboard()
    {
        $tenantsCount = Tenant::count();
        $activeTenantsCount = Tenant::where('activo', true)->count();
        $usersCount = User::whereNotNull('tenant_id')->count();

        // Calculate global API consumption statistics
        $currentPeriod = date('Y-m');
        $usageStats = ApiUsage::select(
            DB::raw('SUM(conteo) as total_queries'),
            DB::raw('SUM(costo_estimado) as total_cost'),
            DB::raw('SUM(ingreso_estimado) as total_revenue')
        )->first();

        $monthlyStats = ApiUsage::select(
            DB::raw('SUM(conteo) as total_queries'),
            DB::raw('SUM(costo_estimado) as total_cost'),
            DB::raw('SUM(ingreso_estimado) as total_revenue')
        )->where('periodo', $currentPeriod)->first();

        // Get usage grouped by service
        $serviceStats = ApiUsage::select('servicio', DB::raw('SUM(conteo) as total_queries'))
            ->groupBy('servicio')
            ->get();

        // Get recent activity
        $recentActivity = Activity::orderBy('created_at', 'desc')->take(5)->get();

        return view('superadmin.dashboard', compact(
            'tenantsCount',
            'activeTenantsCount',
            'usersCount',
            'usageStats',
            'monthlyStats',
            'serviceStats',
            'recentActivity'
        ));
    }

    /**
     * Display a listing of the tenants.
     */
    public function index()
    {
        $tenants = Tenant::withCount('users')->paginate(10);
        return view('superadmin.tenants.index', compact('tenants'));
    }

    /**
     * Show the form for creating a new tenant.
     */
    public function create()
    {
        return view('superadmin.tenants.create');
    }

    /**
     * Store a newly created tenant in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'limite_consultas_mensual' => 'required|integer|min:1',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|string|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $request->name,
                'limite_consultas_mensual' => $request->limite_consultas_mensual,
                'activo' => true,
            ]);

            // 2. Create initial Tenant Admin User
            $user = User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'tenant_id' => $tenant->id,
                'avatar' => 'avatar-1.jpg',
            ]);
            $user->assignRole('tenant_admin');

            // Log activity
            activity()
                ->performedOn($tenant)
                ->causedBy(auth()->user())
                ->log("Creado tenant {$tenant->name} y su administrador {$user->email}");
        });

        return redirect()->route('superadmin.tenants.index')
            ->with('success', 'Tenant y Administrador creados correctamente.');
    }

    /**
     * Show the form for editing the specified tenant.
     */
    public function edit($id)
    {
        $tenant = Tenant::findOrFail($id);
        return view('superadmin.tenants.edit', compact('tenant'));
    }

    /**
     * Update the specified tenant in storage.
     */
    public function update(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'limite_consultas_mensual' => 'required|integer|min:1',
            'activo' => 'required|boolean',
        ]);

        $tenant->update($request->only('name', 'limite_consultas_mensual', 'activo'));

        // Log activity
        activity()
            ->performedOn($tenant)
            ->causedBy(auth()->user())
            ->log("Actualizado tenant {$tenant->name} - Límite: {$tenant->limite_consultas_mensual}, Activo: " . ($tenant->activo ? 'Sí' : 'No'));

        return redirect()->route('superadmin.tenants.index')
            ->with('success', 'Tenant actualizado correctamente.');
    }

    /**
     * Remove the specified tenant from storage (toggle active state instead of deletion).
     */
    public function destroy($id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->activo = !$tenant->activo;
        $tenant->save();

        $status = $tenant->activo ? 'activado' : 'desactivado';

        // Log activity
        activity()
            ->performedOn($tenant)
            ->causedBy(auth()->user())
            ->log("Cambio de estado del tenant {$tenant->name} a {$status}");

        return redirect()->route('superadmin.tenants.index')
            ->with('success', "Tenant {$status} correctamente.");
    }

    /**
     * Display the general activity logs.
     */
    public function activityLogs(Request $request)
    {
        $logs = Activity::with('causer')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('superadmin.activity-logs', compact('logs'));
    }

    /**
     * Display the immutable background checks query audit logs.
     */
    public function auditLogs(Request $request)
    {
        $logs = AuditLog::with('user', 'tenant')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('superadmin.audit-logs', compact('logs'));
    }

    /**
     * Display the NuFi API integration logs (requests and responses).
     */
    public function apiLogs(Request $request)
    {
        $query = \App\Models\SourceQuery::withoutGlobalScopes()
            ->with('subject', 'tenant', 'result')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            // Fetch all subjects, decrypt them, and filter by name or RFC
            $matchingSubjectIds = \App\Models\Subject::withoutGlobalScopes()
                ->get()
                ->filter(function ($subject) use ($search) {
                    return stripos($subject->name_or_company ?? '', $search) !== false ||
                           stripos($subject->rfc ?? '', $search) !== false;
                })
                ->pluck('id');

            $query->whereIn('subject_id', $matchingSubjectIds);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->input('source_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $queries = $query->paginate(20)->withQueryString();
        $tenants = \App\Models\Tenant::orderBy('name', 'asc')->get();

        return view('superadmin.api-logs', compact('queries', 'tenants'));
    }
}
