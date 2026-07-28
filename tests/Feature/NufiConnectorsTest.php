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

        // Verify 10 jobs dispatched: rfc, csd, siger, sat_listas, marcas, sanciones, litigios, presencia_en_linea, denue, identidad_digital
        Queue::assertPushed(\App\Jobs\ProcessConnectorQuery::class, 10);

        // Verify 10 SourceQuery records in pending state
        $queries = SourceQuery::where('subject_id', $subject->id)->get();
        $this->assertCount(10, $queries);
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
        // persona_fisica (no INE): curp, rfc, csd, sat_listas, lista_nominal, sanciones, litigios, presencia_en_linea, denue, selfie, identidad_digital = 11
        $queries = SourceQuery::where('subject_id', $subject->id)->get();
        $this->assertCount(11, $queries);

        // Verify that query statuses are completed
        foreach ($queries as $q) {
            $q->refresh();
            $this->assertEquals('completed', $q->status);
            $this->assertNotNull($q->result);
            $this->assertIsArray($q->result->raw_payload);
        }

        $auditLogs = AuditLog::where('tenant_id', $user->tenant_id)->get();
        $this->assertCount(11, $auditLogs);

        // Verify api_usage totals
        $usageCount = ApiUsage::where('tenant_id', $user->tenant_id)->sum('conteo');
        $this->assertEquals(11, $usageCount);
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
     * Test CURP connector parsing of real NuFi response payload with nested curpdata.
     */
    public function test_curp_connector_real_nufi_response_mapping()
    {
        config(['background_check.nufi.mock' => false]);
        config(['background_check.nufi.api_key' => 'test-api-key']);

        $subject = new Subject([
            'curp' => 'CAML780401HDFRTL01',
            'name_or_company' => 'LUIS FELIPE CAUDILLO',
            'tipo' => 'persona_fisica',
        ]);

        $connector = new \App\Services\BackgroundCheck\Nufi\NufiCurpConnector();

        \Illuminate\Support\Facades\Http::fake([
            'https://nufi.azure-api.net/curp/v1/consulta' => \Illuminate\Support\Facades\Http::response([
                'body' => [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'resolve!',
                    'data' => [
                        'guid' => '5b3702e1-0d9b-4a3b-bdf2-0b7c58e3c6a6',
                        'curpdata' => [
                            [
                                'curp' => 'CAML780401HDFRTL01',
                                'sexo' => 'HOMBRE',
                                'entidad' => 'CIUDAD DE MEXICO',
                                'nombres' => 'LUIS FELIPE',
                                'statusCurp' => 'RCN',
                                'primerApellido' => 'CAUDILLO',
                                'segundoApellido' => 'MARTINEZ',
                                'fechaNacimiento' => '01/04/1978',
                                'descriptionStatusCurp' => 'Registro de Cambio No Afectando a CURP'
                            ]
                        ]
                    ]
                ],
                'status' => 200
            ], 200)
        ]);

        $res = $connector->execute($subject);

        $this->assertTrue($res['valida']);
        $this->assertEquals('LUIS FELIPE', $res['nombre']);
        $this->assertEquals('CAUDILLO', $res['primer_apellido']);
        $this->assertEquals('MARTINEZ', $res['segundo_apellido']);
        $this->assertEquals('RCN', $res['estatus_curp']);
        $this->assertEquals('Registro de Cambio No Afectando a CURP', $res['description_status_curp']);
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
        $this->assertEquals('500-05-2018-22825 de fecha 17 de agosto de 2018', $mockRes['oficio_oficial']);

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
     * Test CSD processing status in ProcessConnectorQuery.
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

        // Mock Http to return synchronous success with certificados
        \Illuminate\Support\Facades\Http::fake([
            'https://nufi.azure-api.net/certificadosat/v1/consultar/consultar' => \Illuminate\Support\Facades\Http::response([
                'code' => 200,
                'status' => 'success',
                'message' => 'La petición se ha realizado correctamente',
                'data' => [
                    'rfc' => 'CAML7804014N5',
                    'razon_social' => 'CSD Test Company',
                    'certificados' => [
                        [
                            'numero_serie' => '00001000000514203894',
                            'estado' => 'Activo',
                            'tipo' => 'SELLO',
                            'fecha_inicial' => '2022-07-28 16:55:12',
                            'fecha_final' => '2026-07-28 16:55:12',
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Force mock = false for CSD connector in config
        config(['background_check.nufi.mock' => false]);
        config(['background_check.nufi.api_key' => 'test-api-key']);

        $job = new \App\Jobs\ProcessConnectorQuery($sourceQuery, 1);
        $job->handle();

        $sourceQuery->refresh();
        $this->assertEquals('completed', $sourceQuery->status);
        $this->assertCount(1, $sourceQuery->result->processed_data['certificados']);
        $this->assertEquals('00001000000514203894', $sourceQuery->result->processed_data['certificados'][0]['numero_serie']);
    }

    /**
     * Test INE Frente and Reverso connectors mapping and execution.
     */
    public function test_ine_connectors_ocr_processing()
    {
        $user = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($user);

        $project = Project::create(['name' => 'Project 3']);
        $subject = Subject::create([
            'project_id' => $project->id,
            'tipo' => 'persona_fisica',
            'name_or_company' => 'Juan Perez Lopez',
            'rfc' => 'PEHJ8405021H0',
            'consent_granted' => true,
            'consent_date' => now(),
            'consent_legal_basis' => 'Autorización expresa',
            'ine_front_path' => 'ine_documents/test_front.png',
            'ine_back_path' => 'ine_documents/test_back.png',
        ]);

        $runner = new InvestigationRunner();
        $runner->run($subject);

        // 13 jobs: curp, rfc, csd, sat_listas, ine_frente, ine_reverso, lista_nominal, sanciones, litigios, presencia_en_linea, denue, selfie, identidad_digital
        $queries = SourceQuery::where('subject_id', $subject->id)->get();
        $this->assertCount(13, $queries);

        // Verify status
        $frenteQuery = $queries->where('source_type', 'ine_frente')->first();
        $reversoQuery = $queries->where('source_type', 'ine_reverso')->first();

        $this->assertNotNull($frenteQuery);
        $this->assertNotNull($reversoQuery);

        $frenteQuery->refresh();
        $reversoQuery->refresh();

        $this->assertEquals('completed', $frenteQuery->status);
        $this->assertEquals('completed', $reversoQuery->status);

        $this->assertEquals('JUAN', $frenteQuery->result->processed_data['nombre']);
        $this->assertNotEmpty($reversoQuery->result->processed_data['cic']);
    }

    /**
     * Test Sanciones and Litigios connectors mock processing.
     */
    public function test_sanciones_and_litigios_connectors_mapping()
    {
        $user = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($user);

        $project = Project::create(['name' => 'Project 4']);
        // Subject name contains "pep" and "litigio" to trigger positive mocks
        $subject = Subject::create([
            'project_id' => $project->id,
            'tipo' => 'persona_fisica',
            'name_or_company' => 'Juan Perez PEP Litigio',
            'rfc' => 'PEHJ8405021H0',
            'consent_granted' => true,
            'consent_date' => now(),
            'consent_legal_basis' => 'Autorización expresa',
        ]);

        $runner = new InvestigationRunner();
        $runner->run($subject);

        $queries = SourceQuery::where('subject_id', $subject->id)->get();
        
        $sancionesQuery = $queries->where('source_type', 'sanciones')->first();
        $litigiosQuery = $queries->where('source_type', 'litigios')->first();

        $this->assertNotNull($sancionesQuery);
        $this->assertNotNull($litigiosQuery);

        $this->assertEquals('completed', $sancionesQuery->status);
        $this->assertEquals('completed', $litigiosQuery->status);

        $this->assertTrue($sancionesQuery->result->processed_data['encontrado']);
        $this->assertTrue($litigiosQuery->result->processed_data['tiene_juicios']);
    }

    /**
     * Test INE Frente and Reverso connectors mapping with real NuFi API response structure.
     */
    public function test_ine_frente_and_reverso_real_nufi_response_mapping()
    {
        $user = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($user);

        $project = Project::create(['tenant_id' => $user->tenant_id, 'name' => 'Project INE']);
        $subject = Subject::create([
            'project_id' => $project->id,
            'tipo' => 'persona_fisica',
            'name_or_company' => 'Pablo Caudillo Martinez',
            'rfc' => 'CAML7804014N5',
            'ine_front_path' => 'dummy_frente.jpg',
            'ine_back_path' => 'dummy_back.jpg',
            'consent_granted' => true,
        ]);

        \Illuminate\Support\Facades\Storage::fake();
        \Illuminate\Support\Facades\Storage::put('dummy_frente.jpg', 'dummy content');
        \Illuminate\Support\Facades\Storage::put('dummy_back.jpg', 'dummy content');

        \Illuminate\Support\Facades\Http::fake([
            'nufi.azure-api.net/ocr/v4/frente' => \Illuminate\Support\Facades\Http::response([
                'body' => [
                    'code' => 200,
                    'data' => [
                        'id' => '20260728220508_65988100',
                        'ocr' => [
                            'curp' => 'CAML7804014N5',
                            'sexo' => 'HOMBRE',
                            'tipo' => 'G',
                            'clave' => 'CDML7804014N5',
                            'nombre' => 'PABLO',
                            'apellido_paterno' => 'CAUDILLO',
                            'apellido_materno' => 'MARTINEZ',
                            'emision' => '03',
                            'seccion' => '0106',
                            'fecha_nacimiento' => '25/10/1976',
                            'calle_numero' => 'BLVD ADOLFO LOPEZ MATEOS 359',
                            'colonia' => 'SAN ANGEL INN',
                            'municipio' => 'ALVARO OBREGON',
                            'estado' => 'CDMX',
                            'codigo_postal' => '01060',
                        ],
                    ],
                    'status' => 'success',
                ],
                'status' => 200,
            ]),
            'nufi.azure-api.net/ocr/v4/reverso' => \Illuminate\Support\Facades\Http::response([
                'body' => [
                    'code' => 200,
                    'data' => [
                        'id' => '20260728220528_65988100',
                        'ocr' => [
                            'numero_identificador' => '123456789',
                            'ocr' => 'IDMEX123456789',
                        ],
                    ],
                    'status' => 'success',
                ],
                'status' => 200,
            ]),
        ]);

        config(['background_check.nufi.mock' => false]);

        $frenteConnector = new \App\Services\BackgroundCheck\Nufi\NufiIneFrenteConnector();
        $frenteData = $frenteConnector->execute($subject);

        $this->assertEquals('PABLO', $frenteData['nombre']);
        $this->assertEquals('CAUDILLO', $frenteData['apellido_paterno']);
        $this->assertEquals('CDML7804014N5', $frenteData['clave_elector']);
        $this->assertEquals('03', $frenteData['numero_emision']);

        $reversoConnector = new \App\Services\BackgroundCheck\Nufi\NufiIneReversoConnector();
        $reversoData = $reversoConnector->execute($subject);

        $this->assertEquals('123456789', $reversoData['cic']);
        $this->assertEquals('IDMEX123456789', $reversoData['codigo_ocr']);
    }

    /**
     * Test Lista Nominal connector with both mock and real response structures.
     */
    public function test_lista_nominal_connector_mock_and_real_response_mapping()
    {
        $user = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($user);

        $project = Project::create(['tenant_id' => $user->tenant_id, 'name' => 'Project Lista Nominal']);
        $subject = Subject::create([
            'project_id' => $project->id,
            'tipo' => 'persona_fisica',
            'name_or_company' => 'Luis Felipe Caudillo',
            'rfc' => 'CAML7804014N5',
            'consent_granted' => true,
        ]);

        // 1. Mock mode test
        config(['background_check.nufi.mock' => true]);
        $connector = new \App\Services\BackgroundCheck\Nufi\NufiListaNominalConnector();
        $mockData = $connector->execute($subject);

        $this->assertTrue($mockData['valida']);
        $this->assertTrue($mockData['activa']);
        $this->assertEquals('La credencial esta vigente', $mockData['estado']);

        // 2. Real response simulation test
        config(['background_check.nufi.mock' => false]);
        \Illuminate\Support\Facades\Http::fake([
            'nufi.azure-api.net/lista_nominal/validar/v2' => \Illuminate\Support\Facades\Http::response([
                'code' => 200,
                'status' => 'Success',
                'message' => 'Operación exitosa.',
                'data' => [
                    [
                        'information' => "CIC\t232759468\r\nClave de elector\tTMORAN04050519H300\r\nTus datos se encuentran en el Padrón Electoral.",
                        'activa' => true,
                        'estado' => 'La credencial esta vigente',
                        'comentarios' => [
                            'Tus datos se encuentran en el Padrón Electoral.'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $realData = $connector->execute($subject);

        $this->assertTrue($realData['valida']);
        $this->assertTrue($realData['activa']);
        $this->assertEquals('La credencial esta vigente', $realData['estado']);
        $this->assertContains('Tus datos se encuentran en el Padrón Electoral.', $realData['comentarios']);
    }
}
