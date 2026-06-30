<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantCrudAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed database
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * Test Tenant Admin user CRUD permissions.
     */
    public function test_tenant_admin_can_manage_tenant_users()
    {
        $adminA = User::where('email', 'admin@alfa.com')->firstOrFail();
        $this->actingAs($adminA);

        // 1. Index
        $response = $this->get(route('tenant.users.index'));
        $response->assertStatus(200);

        // 2. Create User
        $response = $this->post(route('tenant.users.store'), [
            'name' => 'Nuevo Investigador Alfa',
            'email' => 'new_inv@alfa.com',
            'role' => 'investigador',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertRedirect(route('tenant.users.index'));

        // Verify user was created with correct tenant_id and role
        $newUser = User::where('email', 'new_inv@alfa.com')->firstOrFail();
        $this->assertEquals($adminA->tenant_id, $newUser->tenant_id);
        $this->assertTrue($newUser->hasRole('investigador'));

        // 3. Edit User
        $response = $this->put(route('tenant.users.update', $newUser->id), [
            'name' => 'Investigador Alfa Modificado',
            'email' => 'new_inv@alfa.com',
            'role' => 'tenant_admin',
        ]);
        $response->assertRedirect(route('tenant.users.index'));
        $newUser->refresh();
        $this->assertEquals('Investigador Alfa Modificado', $newUser->name);
        $this->assertTrue($newUser->hasRole('tenant_admin'));

        // 4. Delete User
        $response = $this->delete(route('tenant.users.destroy', $newUser->id));
        $response->assertRedirect(route('tenant.users.index'));
        $this->assertNull(User::find($newUser->id));
    }

    /**
     * Test that Investigator user is barred from user CRUD.
     */
    public function test_investigator_cannot_manage_tenant_users()
    {
        $investigatorA = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($investigatorA);

        // 1. Index
        $response = $this->get(route('tenant.users.index'));
        $response->assertStatus(403); // Forbidden

        // 2. Create
        $response = $this->post(route('tenant.users.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked@alfa.com',
            'role' => 'investigador',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test tenant admin & investigator can manage projects and subjects.
     */
    public function test_tenant_users_can_manage_projects_and_subjects()
    {
        $investigatorA = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($investigatorA);

        // 1. Create project
        $response = $this->post(route('tenant.projects.store'), [
            'name' => 'Proyecto M3 Alfa',
            'description' => 'Descripcion M3 Alfa',
        ]);
        $response->assertRedirect(route('tenant.projects.index'));

        $project = Project::where('name', 'Proyecto M3 Alfa')->firstOrFail();
        $this->assertEquals($investigatorA->tenant_id, $project->tenant_id);

        // 2. Create subject
        $response = $this->post(route('tenant.subjects.store'), [
            'project_id' => $project->id,
            'tipo' => 'persona_fisica',
            'name_or_company' => 'Juan Perez',
            'rfc' => 'PEHJ8405021H0', // Valido RFC
            'curp' => 'PEHJ840502HDFLNR01', // Valido CURP
            'address' => 'Av. Reforma 123, CDMX',
            'consent_granted' => 1,
            'consent_legal_basis' => 'Ley de Protección de Datos',
        ]);

        $response->assertRedirect(route('tenant.projects.show', $project->id));

        // 3. Verify encryption of PII data in the database, but decryption on Eloquent model
        $subject = Subject::where('name_or_company', 'Juan Perez')->firstOrFail();
        $this->assertEquals('PEHJ8405021H0', $subject->rfc);
        $this->assertEquals('PEHJ840502HDFLNR01', $subject->curp);
        $this->assertEquals('Av. Reforma 123, CDMX', $subject->address);

        // Fetch raw data from database to check that it is indeed encrypted (i.e. starts with cipher text / is not plaintext)
        $rawRow = DB::table('subjects')->where('id', $subject->id)->first();
        $this->assertNotEquals('PEHJ8405021H0', $rawRow->rfc);
        $this->assertNotEquals('PEHJ840502HDFLNR01', $rawRow->curp);
        $this->assertNotEquals('Av. Reforma 123, CDMX', $rawRow->address);
    }
}
