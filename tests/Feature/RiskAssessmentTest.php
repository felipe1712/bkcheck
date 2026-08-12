<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\SourceQuery;
use App\Models\SourceResult;
use App\Models\Subject;
use App\Models\User;
use App\Services\BackgroundCheck\RiskAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_risk_assessment_calculates_100_percent_clean_score()
    {
        $user = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $project = Project::create(['tenant_id' => $user->tenant_id, 'name' => 'Clean Risk Project']);

        $subject = Subject::create([
            'tenant_id' => $user->tenant_id,
            'project_id' => $project->id,
            'tipo' => 'persona_fisica',
            'name_or_company' => 'Juan Limpio Perez',
            'rfc' => 'JUAP800101XYZ',
            'consent_granted' => true,
        ]);

        $service = new RiskAssessmentService();
        $risk = $service->calculateRisk($subject);

        $this->assertEquals(100, $risk['score']);
        $this->assertEquals('Bajo / Mínimo', $risk['nivel_riesgo']);
        $this->assertEquals('MUY ALTA', $risk['confiabilidad_label']);
        $this->assertEquals(180.0, $risk['needle_angle']);
        $this->assertEmpty($risk['penalties']);
    }

    public function test_risk_assessment_applies_penalties_for_alerts()
    {
        $user = User::where('email', 'investigador@alfa.com')->firstOrFail();
        $project = Project::create(['tenant_id' => $user->tenant_id, 'name' => 'Risk Penalty Project']);

        $subject = Subject::create([
            'tenant_id' => $user->tenant_id,
            'project_id' => $project->id,
            'tipo' => 'persona_fisica',
            'name_or_company' => 'Pedro Sancionado',
            'rfc' => 'PEDS800101XYZ',
            'consent_granted' => true,
        ]);

        // Sanciones Alert (-40)
        $qSanc = SourceQuery::create([
            'tenant_id' => $user->tenant_id,
            'subject_id' => $subject->id,
            'source_type' => 'sanciones',
            'status' => 'completed',
        ]);
        SourceResult::create([
            'source_query_id' => $qSanc->id,
            'raw_payload' => [],
            'processed_data' => [
                'encontrado' => true,
                'hits' => [
                    [
                        'lista' => 'OFAC SDN',
                        'nombre_encontrado' => 'Pedro Sancionado',
                    ]
                ]
            ]
        ]);

        // Litigios Alert (-25)
        $qLit = SourceQuery::create([
            'tenant_id' => $user->tenant_id,
            'subject_id' => $subject->id,
            'source_type' => 'litigios',
            'status' => 'completed',
        ]);
        SourceResult::create([
            'source_query_id' => $qLit->id,
            'raw_payload' => [],
            'processed_data' => [
                'tiene_juicios' => true,
            ]
        ]);

        $service = new RiskAssessmentService();
        $risk = $service->calculateRisk($subject);

        // 100 - 40 - 25 = 35 score
        $this->assertEquals(35, $risk['score']);
        $this->assertEquals('Alto', $risk['nivel_riesgo']);
        $this->assertEquals('BAJA', $risk['confiabilidad_label']);
        $this->assertEquals(2, $risk['total_penalties']);
    }
}
