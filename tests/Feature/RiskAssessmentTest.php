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

        // Sanciones Alert (CONFIRMADO_NEGATIVO)
        $qSanc = SourceQuery::create([
            'tenant_id' => $user->tenant_id,
            'subject_id' => $subject->id,
            'source_type' => 'sanciones',
            'status' => 'completed',
            'estado_evaluado' => 'CONFIRMADO_NEGATIVO',
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

        // Litigios Alert (CONFIRMADO_NEGATIVO)
        $qLit = SourceQuery::create([
            'tenant_id' => $user->tenant_id,
            'subject_id' => $subject->id,
            'source_type' => 'litigios',
            'status' => 'completed',
            'estado_evaluado' => 'CONFIRMADO_NEGATIVO',
        ]);
        SourceResult::create([
            'source_query_id' => $qLit->id,
            'raw_payload' => [],
            'processed_data' => [
                'tiene_juicios' => true,
            ]
        ]);

        // Failed API query (NO_CONCLUYENTE - Should NOT decrease points!)
        $qFailed = SourceQuery::create([
            'tenant_id' => $user->tenant_id,
            'subject_id' => $subject->id,
            'source_type' => 'lista_nominal',
            'status' => 'failed',
            'estado_evaluado' => 'NO_CONCLUYENTE',
            'error_message' => 'API Timeout 504',
        ]);

        $service = new RiskAssessmentService();
        $risk = $service->calculateRisk($subject);

        // Cumplimiento PLD index = 100 - 45 - 25 = 30%
        // Global score = 100 * 0.35 + 30 * 0.35 + 100 * 0.15 + 100 * 0.15 = 35 + 10.5 + 15 + 15 = 76% (rounded to 76)
        $this->assertCount(1, $risk['fuentes_pendientes']);
        $this->assertEquals(2, $risk['total_penalties']);
        $this->assertStringContainsString('Validar manualmente', $risk['recomendacion']);
    }
}
