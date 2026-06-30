<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class TenantUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:tenant_admin']);
    }

    /**
     * Display a listing of the tenant users.
     */
    public function index()
    {
        // Users are filtered by tenant_id when the query is run by non-superadmins,
        // but since superadmins are blocked by the middleware, this query is safe.
        // Let's explicitly scope it just in case, using the logged-in user's tenant_id:
        $tenantId = Auth::user()->tenant_id;
        $users = User::where('tenant_id', $tenantId)->paginate(10);

        return view('tenant.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        // Only allow selecting 'tenant_admin' or 'investigador'
        $roles = Role::whereIn('name', ['tenant_admin', 'investigador'])->get();
        return view('tenant.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:tenant_admin,investigador',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenantId,
            'avatar' => 'avatar-1.jpg',
        ]);

        $user->assignRole($request->role);

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Usuario creado para el Tenant: {$user->email} - Rol: {$request->role}");

        return redirect()->route('tenant.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        // Find user, ensuring they belong to the same tenant
        $user = User::where('tenant_id', $tenantId)->findOrFail($id);
        $roles = Role::whereIn('name', ['tenant_admin', 'investigador'])->get();

        return view('tenant.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        $tenantId = Auth::user()->tenant_id;
        $user = User::where('tenant_id', $tenantId)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|string|email|max:255|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:tenant_admin,investigador',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Sync Spatie roles
        $user->syncRoles([$request->role]);

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Usuario actualizado: {$user->email} - Rol: {$request->role}");

        return redirect()->route('tenant.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $user = User::where('tenant_id', $tenantId)->findOrFail($id);

        // Prevent self deletion
        if ($user->id === Auth::id()) {
            return redirect()->route('tenant.users.index')
                ->withErrors(['error' => 'No puedes eliminar tu propia cuenta de usuario.']);
        }

        // Log activity before deletion
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Usuario eliminado: {$user->email}");

        $user->delete();

        return redirect()->route('tenant.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
