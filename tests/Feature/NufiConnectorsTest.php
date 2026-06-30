<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use App\Models\SourceQuery;
use App\Models\SourceResult;
use App\Models\AuditLog;
use App\Models\ApiUsage;
use App\Services\BackgroundCheck\InvestigationRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NufiConnectorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * Test that InvestigationRunner correctly dispatches jobs and respects the quota limit.
     */
    public function test_investigation_runner_dispatches_jobs_and_enforces_quota()
    {
        $user = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($user);

        // Create a project
        $project = Project::create([
            'name' => 'Proyecto Test Conectores',
            'description' => 'Un proyecto para probar conectores.',
        ]);

        // Create a subject (persona_moral so all 5 connectors apply: rfc, csd, siger, sat_listas, marcas)
        $subject = Subject::create([
            'project_id' => $project->id,
            'tipo' => 'persona_moral',
            'name_or_company' => 'Empresa Ficticia SA',
            'rfc' => 'XAXX010101000',
            'consent_granted' => true,
            'consent_date' => now(),
            'consent_legal_basis' => 'Autorización expresa',
        ]);

        // Setup Queue faking to inspect dispatches
        Queue::fake();

        $runner = new InvestigationRunner();
        $runner->run($subject);

        // Verify that 5 jobs were dispatched (rfc, csd, siger, sat_listas, marcas)
        Queue::assertPushed(\App\Jobs\ProcessConnectorQuery::class, 5);

        // Verify that 5 SourceQuery records were created in pending state
        $queries = SourceQuery::where('subject_id', $subject->id)->get();
        $this->assertCount(5, $queries);
        foreach ($queries as $q) {
            $this->assertEquals('pending', $q->status);
        }
    }

    /**
     * Test job execution updates database states (mock responses).
     */
    public function test_job_execution_creates_results_and_logs()
    {
        $user = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($user);

        $project = Project::create(['name' => 'Project 2']);
        $subject = Subject::create([
            'project_id' => $project->id,
            'tipo' => 'persona_fisica', // 4 connectors apply: rfc, csd, sat_listas, marcas (siger does not apply)
            'name_or_company' => 'Juan Perez',
            'rfc' => 'PEHJ8405021H0',
            'consent_granted' => true,
            'consent_date' => now(),
            'consent_legal_basis' => 'Autorización expresa',
        ]);

        $runner = new InvestigationRunner();
        $runner->run($subject);

        // Under PHPUnit's sync queue driver, the runner's dispatches execute immediately.
        // We can query the results and assert states directly.
        $queries = SourceQuery::where('subject_id', $subject->id)->get();
        $this->assertCount(4, $queries);

        // Verify that query statuses are completed
        foreach ($queries as $q) {
            $q->refresh();
            $this->assertEquals('completed', $q->status);
            $this->assertNotNull($q->result);
            $this->assertIsArray($q->result->raw_payload);
        }

        // Verify that audit logs were populated (4 entries)
        $auditLogs = AuditLog::where('tenant_id', $user->tenant_id)->get();
        $this->assertCount(4, $auditLogs);

        // Verify that api_usage entries were created/updated
        $usageCount = ApiUsage::where('tenant_id', $user->tenant_id)->sum('conteo');
        $this->assertEquals(4, $usageCount);
    }

    /**
     * Test that quota limit is enforced and throws exception when exceeded.
     */
    public function test_runner_blocks_when_quota_exceeded()
    {
        $user = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($user); // Set active user context to assign tenant_id to project
        $tenant = $user->tenant;

        // Set a very low limit (e.g. 2 queries)
        $tenant->update(['limite_consultas_mensual' => 2]);

        $project = Project::create(['name' => 'Project 3']);
        $subject = Subject::create([
            'project_id' => $project->id,
            'tipo' => 'persona_fisica', // Needs 4 queries
            'name_or_company' => 'Juan Perez',
            'rfc' => 'PEHJ8405021H0',
            'consent_granted' => true,
            'consent_date' => now(),
            'consent_legal_basis' => 'Autorización expresa',
        ]);

        $runner = new InvestigationRunner();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Se ha excedido el límite mensual de consultas');

        $runner->run($subject);
    }

    /**
     * Test SAT Listas connector response mapping.
     */
    public function test_sat_listas_connector_mapping()
    {
        $subject = new Subject([
            'rfc' => 'umc990622ag7',
            'name_or_company' => 'Empresa No Localizado Test',
        ]);

        $connector = new \App\Services\BackgroundCheck\Nufi\NufiSatListasConnector();

        // 1. Test Mock Mode
        // It should mock as being in list 69 because the name contains 'No Localizado'
        $mockRes = $connector->execute($subject);
        $this->assertTrue($mockRes['en_lista_69']);
        $this->assertFalse($mockRes['en_lista_69b']);
        $this->assertEquals('500-05-2026-OF-1024', $mockRes['oficio_oficial']);

        // 2. Test callApi mapping with a mock HTTP response
        // Disable mock mode in connector to force callApi
        $reflector = new \ReflectionClass($connector);
        $prop = $reflector->getProperty('isMock');
        $prop->setAccessible(true);
        $prop->setValue($connector, false);

        $apiKeyProp = $reflector->getProperty('apiKey');
        $apiKeyProp->setAccessible(true);
        $apiKeyProp->setValue($connector, 'test-api-key');

        // Mock Http Facade
        \Illuminate\Support\Facades\Http::fake([
            'https://nufi.azure-api.net/contribuyentes_69/v1/no_localizados' => \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'code' => 200,
                'message' => 'operacion realizada con exito',
                'count' => 0,
                'data' => []
            ], 200),
            'https://nufi.azure-api.net/contribuyentes/v1/obtener_contribuyente' => \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'message' => 'Datos obtenidos',
                'data' => [
                    [
                        'rfc' => 'AASA420925JN5',
                        'nombre_contribuyente' => 'adame silva aurelio',
                        'situacion_contribuyente' => 'definitivo',
                        'fecha_oficio_global_definitivos' => 'OF-DEF-12345',
                        'fecha_publi_dof_definitivos' => '2026-06-01',
                    ]
                ]
            ], 200)
        ]);

        $apiRes = $connector->execute($subject);
        $this->assertFalse($apiRes['en_lista_69']);
        $this->assertTrue($apiRes['en_lista_69b']);
        $this->assertEquals('definitivo', $apiRes['estatus_69b']);
        $this->assertEquals('OF-DEF-12345', $apiRes['oficio_oficial']);
        $this->assertEquals('2026-06-01', $apiRes['fecha_publicacion']);
    }

    /**
     * Test Marcas connector response mapping.
     */
    public function test_marcas_connector_mapping()
    {
        $subject = new Subject([
            'name_or_company' => 'Nufi Corporation',
        ]);

        $connector = new \App\Services\BackgroundCheck\Nufi\NufiMarcasConnector();

        // 1. Test Mock Mode
        $mockRes = $connector->execute($subject);
        $this->assertCount(1, $mockRes['marcas']);
        $this->assertEquals('NUFI CORPORATION', $mockRes['marcas'][0]['denominacion']);

        // 2. Test callApi mapping with a mock HTTP response
        $reflector = new \ReflectionClass($connector);
        $prop = $reflector->getProperty('isMock');
        $prop->setAccessible(true);
        $prop->setValue($connector, false);

        $apiKeyProp = $reflector->getProperty('apiKey');
        $apiKeyProp->setAccessible(true);
        $apiKeyProp->setValue($connector, 'test-api-key');

        // Mock Http Facade
        \Illuminate\Support\Facades\Http::fake([
            'https://nufi.azure-api.net/trademark/v1/find?Ocp-Apim-Subscription-Key=test-api-key' => \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'code' => 200,
                'data' => [
                    [
                        'expediente' => 'EXP-12345',
                        'registro' => 'REG-98765',
                        'name' => 'Nufi Brand',
                        'owner' => 'Nufi SAPI',
                        'class' => 42,
                        'concession_date' => '2026-06-01',
                        'status' => 'REGISTRADA'
                    ]
                ]
            ], 200)
        ]);

        $apiRes = $connector->execute($subject);
        $this->assertCount(1, $apiRes['marcas']);
        $this->assertEquals('EXP-12345', $apiRes['marcas'][0]['numero_expediente']);
        $this->assertEquals('REG-98765', $apiRes['marcas'][0]['numero_registro']);
        $this->assertEquals('Nufi Brand', $apiRes['marcas'][0]['denominacion']);
        $this->assertEquals('Nufi SAPI', $apiRes['marcas'][0]['titular']);
        $this->assertEquals(42, $apiRes['marcas'][0]['clase_nice']);
        $this->assertEquals('2026-06-01', $apiRes['marcas'][0]['fecha_concesion']);
    }

    /**
     * Test CSD async processing status in ProcessConnectorQuery.
     */
    public function test_csd_async_processing_status()
    {
        $tenant = Tenant::firstOrFail();
        $project = Project::create([
            'name' => 'Project CSD Test',
            'tenant_id' => $tenant->id,
        ]);
        $subject = Subject::create([
            'project_id' => $project->id,
            'tipo' => 'persona_fisica',
            'name_or_company' => 'CSD Test Company',
            'rfc' => 'CAML7804014N5',
            'tenant_id' => $tenant->id,
        ]);

        $sourceQuery = SourceQuery::create([
            'tenant_id' => $tenant->id,
            'subject_id' => $subject->id,
            'source_type' => 'csd',
            'status' => 'pending',
        ]);

        // Mock Http to return async success with uuid
        \Illuminate\Support\Facades\Http::fake([
            'https://nufi.azure-api.net/certificadosat/v1/consultar/async' => \Illuminate\Support\Facades\Http::response([
                'code' => 200,
                'status' => 'success',
                'uuid' => 'test-async-uuid-5678',
                'message' => 'alta de la operacion realizada con exito.'
            ], 200)
        ]);

        // Force mock = false for CSD connector in config
        config(['background_check.nufi.mock' => false]);
        config(['background_check.nufi.api_key' => 'test-api-key']);

        $job = new \App\Jobs\ProcessConnectorQuery($sourceQuery, 1);
        $job->handle();

        $sourceQuery->refresh();
        // CSD has uuid, so status should remain 'processing', not 'completed'
        $this->assertEquals('processing', $sourceQuery->status);
        $this->assertEquals('test-async-uuid-5678', $sourceQuery->result->processed_data['uuid']);
    }
}
