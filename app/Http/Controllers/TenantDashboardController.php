<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Subject;
use App\Models\ApiUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TenantDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:tenant_admin|investigador']);
    }

    /**
     * Show the tenant dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (!$tenant || !$tenant->activo) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta corporativa está desactivada o suspendida.']);
        }

        // Project and subject counts (automatically isolated by HasTenant trait)
        $projectsCount = Project::count();
        $subjectsCount = Subject::count();

        // Calculate API consumption for current month
        $currentPeriod = date('Y-m');
        $monthlyUsage = ApiUsage::where('periodo', $currentPeriod)->sum('conteo');

        // Recent subjects list
        $recentSubjects = Subject::with('project')->orderBy('created_at', 'desc')->take(5)->get();

        return view('tenant.dashboard', compact(
            'tenant',
            'projectsCount',
            'subjectsCount',
            'monthlyUsage',
            'recentSubjects'
        ));
    }

    /**
     * Show the tenant api consumption/billing details.
     */
    public function consumption(Request $request)
    {
        if (!Auth::user()->hasRole('tenant_admin')) {
            abort(403, 'No tienes permisos para ver este panel de consumo.');
        }

        $tenant = Auth::user()->tenant;
        
        // Fetch all unique periods available in the api_usage for this tenant to populate the select filter
        $availablePeriods = ApiUsage::where('tenant_id', $tenant->id)
            ->select('periodo')
            ->distinct()
            ->orderBy('periodo', 'desc')
            ->pluck('periodo')
            ->toArray();

        // Default to current period if not provided
        $currentPeriod = date('Y-m');
        if (!in_array($currentPeriod, $availablePeriods)) {
            array_unshift($availablePeriods, $currentPeriod);
        }

        $selectedPeriod = $request->query('period', $currentPeriod);

        // Fetch usage records for this tenant and selected period
        $usages = ApiUsage::where('tenant_id', $tenant->id)
            ->where('periodo', $selectedPeriod)
            ->get();

        $totalQueries = $usages->sum('conteo');
        $totalCost = $usages->sum('ingreso_estimado'); // Billed price to tenant

        // Calculate yearly totals based on selected year
        $selectedYear = substr($selectedPeriod, 0, 4);
        $yearlyUsage = ApiUsage::where('tenant_id', $tenant->id)
            ->where('periodo', 'like', "{$selectedYear}-%")
            ->get();
        $yearlyQueries = $yearlyUsage->sum('conteo');
        $yearlyCost = $yearlyUsage->sum('ingreso_estimado');

        // Fetch detailed logs from audit_logs for the selected period
        $startDate = $selectedPeriod . '-01 00:00:00';
        $endDate = date('Y-m-t', strtotime($startDate)) . ' 23:59:59';
        
        $auditLogs = \App\Models\AuditLog::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('tenant.consumption', compact(
            'tenant',
            'availablePeriods',
            'selectedPeriod',
            'usages',
            'totalQueries',
            'totalCost',
            'yearlyQueries',
            'yearlyCost',
            'auditLogs'
        ));
    }
}
