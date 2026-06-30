<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class SuperAdminUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin']);
    }

    /**
     * Display a listing of all users, optionally filtered by tenant.
     */
    public function index(Request $request)
    {
        $tenantId = $request->query('tenant_id');
        
        $query = User::with('tenant', 'roles');

        if ($tenantId !== null && $tenantId !== '') {
            $query->where('tenant_id', $tenantId);
        }

        $users = $query->paginate(15)->withQueryString();
        $tenants = Tenant::orderBy('name', 'asc')->get();

        return view('superadmin.users.index', compact('users', 'tenants', 'tenantId'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $tenants = Tenant::orderBy('name', 'asc')->get();
        $roles = Role::all();

        return view('superadmin.users.create', compact('tenants', 'roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        // Custom validation logic
        if ($request->role === 'super_admin' && $request->filled('tenant_id')) {
            return back()->withErrors(['tenant_id' => 'Un Super Administrador Global no puede pertenecer a un Tenant/Cliente.'])->withInput();
        }

        if ($request->role !== 'super_admin' && !$request->filled('tenant_id')) {
            return back()->withErrors(['tenant_id' => 'Los roles de Tenant Admin o Investigador deben pertenecer a un Tenant/Cliente.'])->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $request->role === 'super_admin' ? null : $request->tenant_id,
            'avatar' => 'avatar-1.jpg',
            'activo' => true,
        ]);

        $user->assignRole($request->role);

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Super Admin creó usuario: {$user->email} - Rol: {$request->role}");

        return redirect()->route('superadmin.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $tenants = Tenant::orderBy('name', 'asc')->get();
        $roles = Role::all();

        return view('superadmin.users.edit', compact('user', 'tenants', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|string|email|max:255|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        // Custom validation logic
        if ($request->role === 'super_admin' && $request->filled('tenant_id')) {
            return back()->withErrors(['tenant_id' => 'Un Super Administrador Global no puede pertenecer a un Tenant/Cliente.'])->withInput();
        }

        if ($request->role !== 'super_admin' && !$request->filled('tenant_id')) {
            return back()->withErrors(['tenant_id' => 'Los roles de Tenant Admin o Investigador deben pertenecer a un Tenant/Cliente.'])->withInput();
        }

        // Prevent blocking/changing role of self if it changes vital permissions
        if ($user->id === Auth::id() && $request->role !== 'super_admin') {
            return back()->withErrors(['role' => 'No puedes cambiar tu propio rol de Super Administrador.'])->withInput();
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'tenant_id' => $request->role === 'super_admin' ? null : $request->tenant_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Super Admin actualizó usuario: {$user->email} - Rol: {$request->role}");

        return redirect()->route('superadmin.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return redirect()->route('superadmin.users.index')
                ->withErrors(['error' => 'No puedes eliminar tu propia cuenta de usuario.']);
        }

        // Log activity before deletion
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Super Admin eliminó usuario: {$user->email}");

        $user->delete();

        return redirect()->route('superadmin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    /**
     * Toggle the active status of the specified user.
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return redirect()->route('superadmin.users.index')
                ->withErrors(['error' => 'No puedes desactivar tu propia cuenta de usuario.']);
        }

        $user->activo = !$user->activo;
        $user->save();

        $status = $user->activo ? 'activado' : 'desactivado/bloqueado';

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Super Admin cambió estado del usuario {$user->email} a {$status}");

        return redirect()->route('superadmin.users.index')
            ->with('success', "Usuario {$status} correctamente.");
    }
}
