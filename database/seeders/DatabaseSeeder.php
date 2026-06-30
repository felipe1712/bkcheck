<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 1. Run Roles and Permissions Seeder
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Create Global Super Admin User (tenant_id = null)
        $superAdmin = User::create([
            'name' => 'Super Administrador Global',
            'email' => 'superadmin@atlas.com',
            'password' => Hash::make('password'),
            'tenant_id' => null,
            'avatar' => 'avatar-1.jpg',
        ]);
        $superAdmin->assignRole('super_admin');

        // 3. Create Tenant A
        $tenantA = Tenant::create([
            'name' => 'Consultoría Alfa',
            'limite_consultas_mensual' => 150,
            'activo' => true,
        ]);

        // Tenant A: Admin
        $adminA = User::create([
            'name' => 'Admin Alfa',
            'email' => 'admin@alfa.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenantA->id,
            'avatar' => 'avatar-1.jpg',
        ]);
        $adminA->assignRole('tenant_admin');

        // Tenant A: Investigator
        $investigatorA = User::create([
            'name' => 'Investigador Alfa',
            'email' => 'investigador@alfa.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenantA->id,
            'avatar' => 'avatar-1.jpg',
        ]);
        $investigatorA->assignRole('investigador');

        // 4. Create Tenant B
        $tenantB = Tenant::create([
            'name' => 'Investigaciones Beta',
            'limite_consultas_mensual' => 50,
            'activo' => true,
        ]);

        // Tenant B: Admin
        $adminB = User::create([
            'name' => 'Admin Beta',
            'email' => 'admin@beta.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenantB->id,
            'avatar' => 'avatar-1.jpg',
        ]);
        $adminB->assignRole('tenant_admin');

        // Tenant B: Investigator
        $investigatorB = User::create([
            'name' => 'Investigador Beta',
            'email' => 'investigador@beta.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenantB->id,
            'avatar' => 'avatar-1.jpg',
        ]);
        $investigatorB->assignRole('investigador');
    }
}
