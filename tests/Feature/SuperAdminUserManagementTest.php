<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * Test super admin can access users index, filtered or unfiltered.
     */
    public function test_super_admin_can_view_users_list_and_filter()
    {
        $superAdmin = User::where('email', 'superadmin@avalid.com.mx')->firstOrFail();
        $tenantA = Tenant::where('name', 'Consultoría Alfa')->firstOrFail();

        $this->actingAs($superAdmin);

        // View all users
        $response = $this->get(route('superadmin.users.index'));
        $response->assertStatus(200);
        $response->assertSee('superadmin@avalid.com.mx');
        $response->assertSee('admin@alfa.com');
        $response->assertSee('admin@beta.com');

        // View filtered by Tenant A
        $response = $this->get(route('superadmin.users.index', ['tenant_id' => $tenantA->id]));
        $response->assertStatus(200);
        $response->assertSee('admin@alfa.com');
        $response->assertDontSee('admin@beta.com');
    }

    /**
     * Test non-super admin cannot access super admin user management.
     */
    public function test_non_super_admin_cannot_access_user_management()
    {
        $adminA = User::where('email', 'admin@alfa.com')->firstOrFail();
        $this->actingAs($adminA);

        $response = $this->get(route('superadmin.users.index'));
        $response->assertStatus(403);
    }

    /**
     * Test super admin user creation and validations.
     */
    public function test_super_admin_user_creation_and_validation()
    {
        $superAdmin = User::where('email', 'superadmin@avalid.com.mx')->firstOrFail();
        $tenantA = Tenant::where('name', 'Consultoría Alfa')->firstOrFail();

        $this->actingAs($superAdmin);

        // 1. Create a valid Tenant Admin
        $response = $this->post(route('superadmin.users.store'), [
            'name' => 'New Tenant Admin',
            'email' => 'newtenantadmin@example.com',
            'role' => 'tenant_admin',
            'tenant_id' => $tenantA->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertRedirect(route('superadmin.users.index'));
        $newUser = User::where('email', 'newtenantadmin@example.com')->firstOrFail();
        $this->assertEquals($tenantA->id, $newUser->tenant_id);
        $this->assertTrue($newUser->hasRole('tenant_admin'));

        // 2. Try to create a Super Admin with a Tenant
        $response = $this->post(route('superadmin.users.store'), [
            'name' => 'Invalid Super Admin',
            'email' => 'invalid_sa@example.com',
            'role' => 'super_admin',
            'tenant_id' => $tenantA->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasErrors(['tenant_id']);
        $this->assertNull(User::where('email', 'invalid_sa@example.com')->first());

        // 3. Try to create a Tenant Admin without a Tenant
        $response = $this->post(route('superadmin.users.store'), [
            'name' => 'Invalid Tenant Admin',
            'email' => 'invalid_ta@example.com',
            'role' => 'tenant_admin',
            'tenant_id' => null,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasErrors(['tenant_id']);
        $this->assertNull(User::where('email', 'invalid_ta@example.com')->first());
    }

    /**
     * Test super admin user update and role changing.
     */
    public function test_super_admin_user_update()
    {
        $superAdmin = User::where('email', 'superadmin@avalid.com.mx')->firstOrFail();
        $tenantB = Tenant::where('name', 'Investigaciones Beta')->firstOrFail();
        $investigatorA = User::where('email', 'investigador@alfa.com')->firstOrFail();

        $this->actingAs($superAdmin);

        $response = $this->put(route('superadmin.users.update', $investigatorA->id), [
            'name' => 'Investigador Alfa Modificado',
            'email' => 'investigador_mod@alfa.com',
            'role' => 'tenant_admin',
            'tenant_id' => $tenantB->id,
        ]);

        $response->assertRedirect(route('superadmin.users.index'));
        $investigatorA->refresh();
        $this->assertEquals('Investigador Alfa Modificado', $investigatorA->name);
        $this->assertEquals('investigador_mod@alfa.com', $investigatorA->email);
        $this->assertEquals($tenantB->id, $investigatorA->tenant_id);
        $this->assertTrue($investigatorA->hasRole('tenant_admin'));
        $this->assertFalse($investigatorA->hasRole('investigador'));
    }

    /**
     * Test status toggling, blocking/unblocking, and self-exclusion.
     */
    public function test_toggle_user_status_and_self_exclusion()
    {
        $superAdmin = User::where('email', 'superadmin@avalid.com.mx')->firstOrFail();
        $investigatorA = User::where('email', 'investigador@alfa.com')->firstOrFail();

        $this->actingAs($superAdmin);

        // 1. Toggle status of investigator
        $this->assertTrue($investigatorA->activo);
        $response = $this->patch(route('superadmin.users.toggle-status', $investigatorA->id));
        $response->assertRedirect(route('superadmin.users.index'));
        $investigatorA->refresh();
        $this->assertFalse($investigatorA->activo);

        // 2. Toggle status again
        $response = $this->patch(route('superadmin.users.toggle-status', $investigatorA->id));
        $investigatorA->refresh();
        $this->assertTrue($investigatorA->activo);

        // 3. Try to toggle status of self
        $response = $this->patch(route('superadmin.users.toggle-status', $superAdmin->id));
        $response->assertSessionHasErrors(['error']);
        $superAdmin->refresh();
        $this->assertTrue($superAdmin->activo);
    }

    /**
     * Test user deletion and self-exclusion.
     */
    public function test_delete_user_and_self_exclusion()
    {
        $superAdmin = User::where('email', 'superadmin@avalid.com.mx')->firstOrFail();
        $investigatorA = User::where('email', 'investigador@alfa.com')->firstOrFail();

        $this->actingAs($superAdmin);

        // Delete investigator
        $response = $this->delete(route('superadmin.users.destroy', $investigatorA->id));
        $response->assertRedirect(route('superadmin.users.index'));
        $this->assertNull(User::find($investigatorA->id));

        // Try to delete self
        $response = $this->delete(route('superadmin.users.destroy', $superAdmin->id));
        $response->assertSessionHasErrors(['error']);
        $this->assertNotNull(User::find($superAdmin->id));
    }
}
