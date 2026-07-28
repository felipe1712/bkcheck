<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use App\Models\SourceQuery;
use App\Models\SourceResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestigationAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * Test triggering an investigation creates the queries and redirects to subject details.
     */
    public function test_trigger_investigation_routes_correctly()
    {
        $user = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($user);

        $project = Project::create(['name' => 'Project M5 Test']);
        $subject = Subject::create([
            'project_id' => $project->id,
            'tipo' => 'persona_fisica',
            'name_or_company' => 'Juan Test Perez',
            'rfc' => 'PEHJ8405021H0',
            'tier_level' => 4,
            'consent_granted' => true,
            'consent_date' => now(),
            'consent_legal_basis' => 'Autorización',
        ]);

        $response = $this->post(route('tenant.subjects.investigate', $subject->id));
        
        $response->assertRedirect(route('tenant.subjects.show', $subject->id));
        $response->assertSessionHas('success');

        // Confirm that the source queries were created in database
        $queries = SourceQuery::where('subject_id', $subject->id)->get();
        // Since it is persona_fisica, it dispatches 10 queries (including selfie/liveness):
        // curp, rfc, csd, sat_listas, marcas, sanciones, litigios, presencia_en_linea, denue, selfie
        $this->assertCount(10, $queries);
    }

    /**
     * Test that downloading a PDF report returns the PDF binary stream with correct headers.
     */
    public function test_pdf_report_generates_and_streams_successfully()
    {
        $user = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($user);

        $project = Project::create(['name' => 'Project M5 Report Test']);
        $subject = Subject::create([
            'project_id' => $project->id,
            'tipo' => 'persona_moral',
            'name_or_company' => 'Compania de Reportes SA',
            'rfc' => 'XAXX010101000',
            'tier_level' => 4,
            'consent_granted' => true,
            'consent_date' => now(),
            'consent_legal_basis' => 'Autorización',
        ]);

        // Run the runner to dispatch and execute (using PHPUnit's sync queue driver, they complete immediately)
        $runner = new \App\Services\BackgroundCheck\InvestigationRunner();
        $runner->run($subject);

        // Fetch completed queries
        $queries = SourceQuery::where('subject_id', $subject->id)->get();
        // persona_moral: rfc, csd, siger, sat_listas, marcas, sanciones, litigios, presencia_en_linea, denue = 9
        $this->assertCount(9, $queries);

        // Send request to report download route
        $response = $this->get(route('tenant.subjects.report', $subject->id));
        
        // Assert response status is 200 (Success)
        $response->assertStatus(200);
        
        // Assert Content-Type header matches PDF
        $response->assertHeader('Content-Type', 'application/pdf');
        
        // Assert content contains some expected strings like PDF metadata or content bytes
        $this->assertStringContainsString('%PDF', $response->getContent());
    }

    /**
     * Test that Tenant Admin can view the consumption and billing page.
     */
    public function test_tenant_admin_can_view_consumption_page()
    {
        $admin = User::where('email', 'admin@alfa.com')->firstOrFail();
        $this->actingAs($admin);

        $response = $this->get(route('tenant.consumption'));
        $response->assertStatus(200);
        $response->assertViewHas('usages');
        $response->assertViewHas('totalCost');
    }

    /**
     * Test that Investigator is blocked from viewing the consumption and billing page.
     */
    public function test_investigator_cannot_view_consumption_page()
    {
        $investigator = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($investigator);

        $response = $this->get(route('tenant.consumption'));
        $response->assertStatus(403);
    }

    /**
     * Test triggering a single source query investigation.
     */
    public function test_single_source_investigation_triggers_correctly()
    {
        $user = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $this->actingAs($user);

        $project = Project::create(['name' => 'Project M9 Test']);
        $subject = Subject::create([
            'project_id' => $project->id,
            'tipo' => 'persona_fisica',
            'name_or_company' => 'Juan Test Perez',
            'rfc' => 'PEHJ8405021H0',
            'consent_granted' => true,
            'consent_date' => now(),
            'consent_legal_basis' => 'Autorización',
        ]);

        // Trigger a single query (rfc)
        $response = $this->post(route('tenant.subjects.investigate.source', [$subject->id, 'rfc']));
        
        $response->assertRedirect(route('tenant.subjects.show', $subject->id));
        $response->assertSessionHas('success');

        // Confirm that only the rfc query was created in database
        $queries = SourceQuery::where('subject_id', $subject->id)->get();
        $this->assertCount(1, $queries);
        $this->assertEquals('rfc', $queries->first()->source_type);
    }
}
