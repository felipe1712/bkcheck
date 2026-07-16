<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Subject;
use App\Models\Project;
use App\Models\SourceQuery;
use App\Models\SourceResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminApiLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * Test that super admin can access API logs page and apply various filters.
     */
    public function test_super_admin_can_access_api_logs_and_apply_filters()
    {
        $superAdmin = User::where('email', 'superadmin@avalid.com.mx')->firstOrFail();
        $tenantA = Tenant::where('name', 'Consultoría Alfa')->firstOrFail();
        $tenantB = Tenant::where('name', 'Investigaciones Beta')->firstOrFail();

        // Let's create projects, subjects, queries and results for testing
        $projectA = Project::create([
            'name' => 'Project A',
            'tenant_id' => $tenantA->id,
        ]);
        $subjectA = Subject::create([
            'project_id' => $projectA->id,
            'tipo' => 'persona_fisica',
            'name_or_company' => 'Luis Felipe Caudillo',
            'rfc' => 'CAML7804014N5',
            'consent_granted' => true,
            'tenant_id' => $tenantA->id,
        ]);
        $queryA = SourceQuery::create([
            'tenant_id' => $tenantA->id,
            'subject_id' => $subjectA->id,
            'source_type' => 'rfc',
            'status' => 'completed',
        ]);
        SourceResult::create([
            'source_query_id' => $queryA->id,
            'raw_payload' => [
                'url' => 'https://nufi.azure-api.net/sat/v1/rfc/validador',
                'method' => 'POST',
                'headers' => ['Ocp-Apim-Subscription-Key' => '***9d6d'],
                'body' => ['rfc' => 'CAML7804014N5'],
                'response' => ['status' => 200, 'body' => ['valid' => true]]
            ],
            'processed_data' => ['valid' => true],
        ]);

        $projectB = Project::create([
            'name' => 'Project B',
            'tenant_id' => $tenantB->id,
        ]);
        $subjectB = Subject::create([
            'project_id' => $projectB->id,
            'tipo' => 'persona_moral',
            'name_or_company' => 'Empresa Alfa SA',
            'rfc' => 'EAL010101AA0',
            'consent_granted' => true,
            'tenant_id' => $tenantB->id,
        ]);
        $queryB = SourceQuery::create([
            'tenant_id' => $tenantB->id,
            'subject_id' => $subjectB->id,
            'source_type' => 'siger',
            'status' => 'failed',
            'error_message' => 'API Timeout',
        ]);
        SourceResult::create([
            'source_query_id' => $queryB->id,
            'raw_payload' => [
                'url' => 'https://nufi.azure-api.net/siger/v1/consulta',
                'method' => 'POST',
                'headers' => ['Ocp-Apim-Subscription-Key' => '***9d6d'],
                'body' => ['razon_social' => 'Empresa Alfa SA'],
                'response' => ['status' => 500, 'body' => ['message' => 'Internal server error']]
            ],
            'processed_data' => [],
        ]);

        // Login as Super Admin
        $this->actingAs($superAdmin);

        // 1. Visit the main logs list page
        $response = $this->get(route('superadmin.api-logs'));
        $response->assertStatus(200);
        $response->assertSee('Luis Felipe Caudillo');
        $response->assertSee('Empresa Alfa SA');

        // 2. Search by subject name
        $response = $this->get(route('superadmin.api-logs', ['search' => 'Luis']));
        $response->assertStatus(200);
        $response->assertSee('Luis Felipe Caudillo');
        $response->assertDontSee('Empresa Alfa SA');

        // 3. Search by RFC
        $response = $this->get(route('superadmin.api-logs', ['search' => 'EAL010101AA0']));
        $response->assertStatus(200);
        $response->assertSee('Empresa Alfa SA');
        $response->assertDontSee('Luis Felipe Caudillo');

        // 4. Filter by Tenant A
        $response = $this->get(route('superadmin.api-logs', ['tenant_id' => $tenantA->id]));
        $response->assertStatus(200);
        $response->assertSee('Luis Felipe Caudillo');
        $response->assertDontSee('Empresa Alfa SA');

        // 5. Filter by Source Type
        $response = $this->get(route('superadmin.api-logs', ['source_type' => 'siger']));
        $response->assertStatus(200);
        $response->assertSee('Empresa Alfa SA');
        $response->assertDontSee('Luis Felipe Caudillo');

        // 6. Filter by Status (failed)
        $response = $this->get(route('superadmin.api-logs', ['status' => 'failed']));
        $response->assertStatus(200);
        $response->assertSee('Empresa Alfa SA');
        $response->assertDontSee('Luis Felipe Caudillo');
    }

    /**
     * Test non-super admin cannot access API logs page.
     */
    public function test_non_super_admin_cannot_access_api_logs()
    {
        $adminA = User::where('email', 'admin@alfa.com')->firstOrFail();
        $this->actingAs($adminA);

        $response = $this->get(route('superadmin.api-logs'));
        $response->assertStatus(403);
    }

    /**
     * Test that incoming NuFi webhook successfully updates query status and saves certificates data.
     */
    public function test_nufi_webhook_callback()
    {
        $tenantA = Tenant::where('name', 'Consultoría Alfa')->firstOrFail();
        $projectA = Project::create([
            'name' => 'Project A',
            'tenant_id' => $tenantA->id,
        ]);
        $subjectA = Subject::create([
            'project_id' => $projectA->id,
            'tipo' => 'persona_fisica',
            'name_or_company' => 'Luis Felipe',
            'rfc' => 'CAML7804014N5',
            'consent_granted' => true,
            'tenant_id' => $tenantA->id,
        ]);
        $query = SourceQuery::create([
            'tenant_id' => $tenantA->id,
            'subject_id' => $subjectA->id,
            'source_type' => 'csd',
            'status' => 'processing',
        ]);
        $result = SourceResult::create([
            'source_query_id' => $query->id,
            'raw_payload' => ['url' => '/certificadosat/v1/consultar/async'],
            'processed_data' => ['uuid' => 'test-uuid-12345'],
        ]);

        // Trigger webhook POST request with uuid matching
        $response = $this->postJson('/api/nufi/webhook', [
            'uuid' => 'test-uuid-12345',
            'status' => 'success',
            'certificados' => [
                [
                    'numero_serie' => '123456789',
                    'estado' => 'ACTIVO',
                    'tipo' => 'CSD',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $query->refresh();
        $this->assertEquals('completed', $query->status);
        
        $result->refresh();
        $this->assertCount(1, $result->processed_data['certificados']);
        $this->assertEquals('123456789', $result->processed_data['certificados'][0]['numero_serie']);
        
        // Trigger webhook POST request with RFC matching fallback
        $query2 = SourceQuery::create([
            'tenant_id' => $tenantA->id,
            'subject_id' => $subjectA->id,
            'source_type' => 'csd',
            'status' => 'processing',
        ]);
        $result2 = SourceResult::create([
            'source_query_id' => $query2->id,
            'raw_payload' => ['url' => '/certificadosat/v1/consultar/async'],
            'processed_data' => [],
        ]);

        $response = $this->postJson('/api/nufi/webhook', [
            'rfc' => 'CAML7804014N5',
            'status' => 'success',
            'certificados' => [
                [
                    'numero_serie' => '987654321',
                    'estado' => 'ACTIVO',
                    'tipo' => 'FIEL',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $query2->refresh();
        $this->assertEquals('completed', $query2->status);
        
        $result2->refresh();
        $this->assertEquals('987654321', $result2->processed_data['certificados'][0]['numero_serie']);
    }
}
