<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage_tenants',
            'manage_tenant_users',
            'view_tenant_usage',
            'manage_projects',
            'manage_subjects',
            'run_investigations',
            'export_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create roles and assign created permissions

        // Super Admin
        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $superAdminRole->givePermissionTo(Permission::all());

        // Tenant Admin
        $tenantAdminRole = Role::findOrCreate('tenant_admin', 'web');
        $tenantAdminRole->givePermissionTo([
            'manage_tenant_users',
            'view_tenant_usage',
            'manage_projects',
            'manage_subjects',
            'run_investigations',
            'export_reports',
        ]);

        // Investigador
        $investigadorRole = Role::findOrCreate('investigador', 'web');
        $investigadorRole->givePermissionTo([
            'manage_projects',
            'manage_subjects',
            'run_investigations',
            'export_reports',
        ]);
    }
}
