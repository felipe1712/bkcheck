<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and default test database records
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * Test that user query scope filters by current logged user tenant.
     */
    public function test_projects_query_is_isolated_by_tenant()
    {
        // Get user for Tenant A
        $userA = User::where('email', 'investigador@alfa.com')->firstOrFail();
        // Get user for Tenant B
        $userB = User::where('email', 'investigador@beta.com')->firstOrFail();

        // 1. Log in as Tenant A user and create a project
        $this->actingAs($userA);
        $projectA = Project::create([
            'name' => 'Proyecto Confidencial A',
            'description' => 'Solo para ojos del Tenant A',
        ]);

        // Assert that the tenant_id was automatically set to Tenant A's ID
        $this->assertEquals($userA->tenant_id, $projectA->tenant_id);

        // 2. Log in as Tenant B user and create a project
        Auth::logout();
        $this->actingAs($userB);
        $projectB = Project::create([
            'name' => 'Proyecto Confidencial B',
            'description' => 'Solo para ojos del Tenant B',
        ]);

        // Assert that the tenant_id was automatically set to Tenant B's ID
        $this->assertEquals($userB->tenant_id, $projectB->tenant_id);

        // 3. Query projects as Tenant B user
        $projectsForB = Project::all();
        $this->assertCount(1, $projectsForB);
        $this->assertEquals('Proyecto Confidencial B', $projectsForB->first()->name);

        // 4. Log in as Tenant A user again and query projects
        Auth::logout();
        $this->actingAs($userA);
        $projectsForA = Project::all();
        $this->assertCount(1, $projectsForA);
        $this->assertEquals('Proyecto Confidencial A', $projectsForA->first()->name);
    }

    /**
     * Test that global Super Admin is not restricted by TenantScope (since tenant_id is null).
     */
    public function test_super_admin_can_see_all_projects()
    {
        $userA = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $userB = User::where('email', 'investigador@beta.com')->firstOrFail();
        $superAdmin = User::where('email', 'superadmin@atlas.com')->firstOrFail();

        // Create projects for both tenants
        $this->actingAs($userA);
        Project::create(['name' => 'Alfa Project']);
        Auth::logout();

        $this->actingAs($userB);
        Project::create(['name' => 'Beta Project']);
        Auth::logout();

        // Act as Super Admin (tenant_id = null)
        $this->actingAs($superAdmin);
        $allProjects = Project::all();

        // Super Admin should see both projects because no global tenant scope is applied to null tenant users
        $this->assertCount(2, $allProjects);
    }
}
