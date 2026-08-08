<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioTemplate;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\ScenarioTemplateManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class ScenarioTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_template_schema_exists(): void
    {
        $this->assertTrue(Schema::hasTable('scenario_templates'));
        $this->assertTrue(Schema::hasColumns('scenario_templates', [
            'id',
            'uuid',
            'organization_id',
            'source_scenario_version_id',
            'name',
            'description',
            'status',
            'created_by_user_id',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_using_template_creates_new_draft_definition_without_history(): void
    {
        [$actor, $organization, $sourceVersion] = $this->publishedSourceFixture();
        $sourceVersion->victims()->create([
            'code' => 'V1',
            'profile' => ['age_group' => 'adult'],
            'injuries' => ['hemorrhage'],
            'initial_state' => ['conscious' => true],
            'expected_priority' => 'immediate',
        ]);
        $sourceVersion->cohorts()->create([
            'label' => 'Grupo A',
            'quantity' => 12,
            'profile' => ['mobility' => 'walking'],
            'triage_category' => 'minor',
            'characteristics' => ['smoke_exposure'],
        ]);
        ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $sourceVersion->id,
            'sequence_number' => 1,
            'status' => 'draft',
        ]);

        $manager = app(ScenarioTemplateManager::class);
        $template = $manager->create($sourceVersion, $organization->id, $actor, 'Template Alpha', 'Modelo institucional');
        $scenario = $manager->use($template, $actor);
        $version = $scenario->versions()->with(['victims', 'cohorts', 'executions'])->sole();

        $this->assertSame($organization->id, $scenario->organization_id);
        $this->assertSame('Template Alpha', $scenario->title);
        $this->assertSame('draft', $scenario->status);
        $this->assertSame(1, $version->version_number);
        $this->assertSame('draft', $version->publication_status);

        foreach (ScenarioVersion::DEFINITION_FIELDS as $field) {
            $this->assertEquals($sourceVersion->getAttribute($field), $version->getAttribute($field));
        }

        $this->assertCount(1, $version->victims);
        $this->assertCount(1, $version->cohorts);
        $this->assertCount(0, $version->executions);
        $this->assertNotSame($sourceVersion->uuid, $version->uuid);
        $this->assertSame('published', $sourceVersion->fresh()->publication_status);
        $this->assertCount(1, $sourceVersion->fresh()->victims);
        $this->assertCount(1, $sourceVersion->fresh()->cohorts);
        $this->assertCount(1, $sourceVersion->fresh()->executions);
    }

    public function test_template_creation_rejects_unpublished_or_cross_organization_source(): void
    {
        [$actor, $organization, $published] = $this->publishedSourceFixture();
        $draft = $this->scenarioVersion($organization, 'Fonte Rascunho', 'draft');
        $foreignOrganization = Organization::create([
            'name' => 'Organização Externa Template',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $foreign = $this->scenarioVersion($foreignOrganization, 'Fonte Externa', 'published');
        $manager = app(ScenarioTemplateManager::class);

        try {
            $manager->create($draft, $organization->id, $actor, 'Inválido Draft', null);
            $this->fail('Draft source should not create a template.');
        } catch (LogicException $exception) {
            $this->assertSame('Scenario templates require a published source version.', $exception->getMessage());
        }

        try {
            $manager->create($foreign, $organization->id, $actor, 'Inválido Tenant', null);
            $this->fail('Cross-organization source should not create a template.');
        } catch (LogicException $exception) {
            $this->assertSame('Scenario template source must belong to the active organization.', $exception->getMessage());
        }

        $this->assertSame('published', $published->fresh()->publication_status);
        $this->assertDatabaseCount('scenario_templates', 0);
    }

    public function test_archived_template_is_terminal_and_cannot_be_used(): void
    {
        [$actor, $organization, $sourceVersion] = $this->publishedSourceFixture();
        $manager = app(ScenarioTemplateManager::class);
        $template = $manager->create($sourceVersion, $organization->id, $actor, 'Template Arquivável', null);

        $archived = $manager->archive($template);

        $this->assertSame('archived', $archived->status);
        $this->assertSame('archived', $manager->archive($archived)->status);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Archived scenario templates cannot be used.');
        $manager->use($archived, $actor);
    }

    public function test_template_mutations_require_scenarios_manage_and_are_tenant_safe(): void
    {
        [$managerUser, $organization, $sourceVersion] = $this->publishedSourceFixture();
        $viewer = $this->user($organization, [AccessAbility::SCENARIOS_VIEW]);

        $this->actingAs($viewer)
            ->withSession(['active_organization_id' => $organization->id])
            ->post(route('scenario-templates.store', $sourceVersion), [
                'name' => 'Bloqueado',
            ])->assertForbidden();

        $response = $this->actingAs($managerUser)
            ->withSession(['active_organization_id' => $organization->id])
            ->post(route('scenario-templates.store', $sourceVersion), [
                'name' => 'Template HTTP',
                'description' => 'Criado pelo fluxo protegido',
            ]);

        $template = ScenarioTemplate::query()->where('name', 'Template HTTP')->firstOrFail();
        $response->assertRedirect(route('scenario-templates.index'));

        $this->actingAs($viewer)
            ->withSession(['active_organization_id' => $organization->id])
            ->post(route('scenario-templates.use', $template))
            ->assertForbidden();
        $this->actingAs($viewer)
            ->withSession(['active_organization_id' => $organization->id])
            ->patch(route('scenario-templates.archive', $template))
            ->assertForbidden();

        $foreignOrganization = Organization::create([
            'name' => 'Tenant Externo HTTP',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $foreignVersion = $this->scenarioVersion($foreignOrganization, 'Fonte Externa HTTP', 'published');

        $this->actingAs($managerUser)
            ->withSession(['active_organization_id' => $organization->id])
            ->post(route('scenario-templates.store', $foreignVersion), [
                'name' => 'Cross Org',
            ])->assertForbidden();
    }

    private function publishedSourceFixture(): array
    {
        $organization = Organization::create([
            'name' => 'Centro de Templates',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $actor = $this->user($organization, [AccessAbility::SCENARIOS_VIEW, AccessAbility::SCENARIOS_MANAGE]);
        $version = $this->scenarioVersion($organization, 'Fonte Publicada', 'published');

        return [$actor, $organization, $version];
    }

    private function scenarioVersion(Organization $organization, string $title, string $publicationStatus): ScenarioVersion
    {
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 25,
            'estimated_casualty_count' => 25,
            'mechanism' => 'Simulação institucional',
            'resources' => ['Rádio', 'IFAK'],
            'learning_objectives' => ['Comando e controle'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Perda de comunicação'],
            'status' => 'draft',
        ]);

        return ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => 25,
            'resources' => $scenario->resources,
            'learning_objectives' => $scenario->learning_objectives,
            'expected_actions' => $scenario->expected_actions,
            'critical_errors' => $scenario->critical_errors,
            'publication_status' => $publicationStatus,
        ]);
    }

    private function user(Organization $organization, array $abilities): User
    {
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'scenario_manager',
            'abilities' => $abilities,
            'granted_at' => now(),
        ]);

        return $user;
    }
}
