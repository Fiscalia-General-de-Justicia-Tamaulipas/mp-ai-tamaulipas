<?php

namespace Tests\Feature;

use App\Jobs\ProcessCaseAnalysisJob;
use App\Models\CaseAnalysis;
use App\Models\CaseEvidence;
use App\Models\User;
use App\Services\CaseAnalysisService;
use App\Services\HypothesisEngine;
use App\Services\ObjectivityAuditEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CaseAnalysisApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_create_a_case_analysis_via_the_api(): void
    {
        $response = $this->postJson('/api/v1/cases/analyze', [
            'external_case_id' => 'EXP-123',
            'external_offense_id' => 42,
            'fact_narrative' => 'Los hechos ocurrieron en la zona urbana y el imputado fue identificado por testigos.',
        ]);

        $response->assertUnauthorized();
    }

    public function test_users_can_create_a_case_analysis_via_the_api(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/cases/analyze', [
            'external_case_id' => 'EXP-123',
            'external_offense_id' => 42,
            'fact_narrative' => 'Los hechos ocurrieron en la zona urbana y el imputado fue identificado por testigos.',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('status', 'draft')
            ->assertJsonStructure([
                'message',
                'analysis_id',
                'status',
            ]);

        Queue::assertPushed(ProcessCaseAnalysisJob::class);
    }

    public function test_users_cannot_view_another_users_case_analysis(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $analysis = CaseAnalysis::create([
            'external_case_id' => 'EXP-999',
            'external_offense_id' => 42,
            'user_id' => $owner->id,
            'status' => 'reviewed',
        ]);

        $response = $this->actingAs($otherUser)
            ->get('/api/v1/cases/'.$analysis->id);

        $response->assertNotFound();
    }

    public function test_users_cannot_update_another_users_case_analysis(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $analysis = CaseAnalysis::create([
            'external_case_id' => 'EXP-998',
            'external_offense_id' => 42,
            'user_id' => $owner->id,
            'status' => 'reviewed',
        ]);

        $response = $this->actingAs($otherUser)
            ->put('/api/v1/cases/'.$analysis->id, [
                'elements_status' => [],
                'suggested_diligences' => [],
                'status' => 'approved',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('case_analyses', [
            'id' => $analysis->id,
            'user_id' => $owner->id,
            'status' => 'reviewed',
        ]);
    }

    public function test_analysis_results_are_persisted_as_structured_evidence(): void
    {
        $analysis = CaseAnalysis::create([
            'external_case_id' => 'EXP-456',
            'external_offense_id' => 42,
            'user_id' => 1,
            'facts_breakdown' => ['narrative' => 'Una persona tomó el objeto.'],
            'status' => 'draft',
        ]);

        $service = $this->mock(CaseAnalysisService::class);
        $service->shouldReceive('runAnalysis')->once()->andReturn([
            'elements_analysis' => [[
                'element_id' => null,
                'status' => 'ACREDITADO',
                'evidence_found' => 'Una persona tomó el objeto.',
            ]],
            'objectivity_audit' => [],
            'suggested_diligences' => [],
        ]);

        (new ProcessCaseAnalysisJob($analysis, 'Una persona tomó el objeto.'))->handle(
            $service,
            app(HypothesisEngine::class),
            app(ObjectivityAuditEngine::class),
        );

        $this->assertDatabaseHas('case_evidence', [
            'case_analysis_id' => $analysis->id,
            'evidence_type' => 'hecho_narrado',
            'source' => 'narrativa_de_la_carpeta',
            'related_fact' => 'Una persona tomó el objeto.',
            'authenticity_status' => 'pendiente',
            'procedural_relation' => 'cargo',
        ]);
    }

    public function test_reanalysis_preserves_user_verified_evidence(): void
    {
        $analysis = CaseAnalysis::create([
            'external_case_id' => 'EXP-789',
            'external_offense_id' => 42,
            'user_id' => 1,
            'status' => 'reviewed',
        ]);

        CaseEvidence::create([
            'case_analysis_id' => $analysis->id,
            'origin' => 'usuario',
            'evidence_type' => 'documento',
            'source' => 'Acta ministerial',
            'related_fact' => 'El acta fue incorporada a la carpeta.',
            'authenticity_status' => 'autentica',
            'valuation_status' => 'relevante',
            'procedural_relation' => 'cargo',
            'is_verified' => true,
        ]);

        $service = $this->mock(CaseAnalysisService::class);
        $service->shouldReceive('runAnalysis')->once()->andReturn([
            'elements_analysis' => [],
            'objectivity_audit' => [],
            'suggested_diligences' => [],
        ]);

        (new ProcessCaseAnalysisJob($analysis, 'Nueva narrativa.'))->handle(
            $service,
            app(HypothesisEngine::class),
            app(ObjectivityAuditEngine::class),
        );

        $this->assertDatabaseHas('case_evidence', [
            'case_analysis_id' => $analysis->id,
            'origin' => 'usuario',
            'related_fact' => 'El acta fue incorporada a la carpeta.',
            'is_verified' => true,
        ]);
    }

    public function test_repeated_ai_quotes_are_stored_once_and_linked_to_all_elements(): void
    {
        $analysis = CaseAnalysis::create([
            'external_case_id' => 'EXP-101',
            'external_offense_id' => 42,
            'user_id' => 1,
            'status' => 'draft',
        ]);

        $service = $this->mock(CaseAnalysisService::class);
        $service->shouldReceive('runAnalysis')->once()->andReturn([
            'facts' => [
                ['id' => 'fact-1', 'information_type' => 'EVIDENCIA', 'content' => 'CUENTA NUMERO 10501492731', 'source' => 'narrativa', 'procedural_relation' => 'cargo'],
                ['id' => 'fact-2', 'information_type' => 'EVIDENCIA', 'content' => 'CUENTA   NUMERO 10501492731', 'source' => 'narrativa', 'procedural_relation' => 'cargo'],
            ],
            'elements_analysis' => [
                ['element_id' => 1, 'status' => 'ACREDITADO', 'evidence_found' => 'CUENTA NUMERO 10501492731', 'supporting_fact_index' => 0],
                ['element_id' => 2, 'status' => 'ACREDITADO', 'evidence_found' => 'CUENTA   NUMERO 10501492731', 'supporting_fact_index' => 0],
                ['element_id' => 3, 'status' => 'ACREDITADO', 'evidence_found' => 'CUENTA NUMERO 10501492731', 'supporting_fact_index' => 1],
            ],
            'objectivity_audit' => [],
            'suggested_diligences' => [],
        ]);

        (new ProcessCaseAnalysisJob($analysis, 'Narrativa.'))->handle(
            $service,
            app(HypothesisEngine::class),
            app(ObjectivityAuditEngine::class),
        );

        $this->assertDatabaseCount('case_evidence', 1);
        $this->assertDatabaseCount('case_facts', 1);
        $this->assertDatabaseCount('case_evidence_offense_elements', 3);
    }
}
