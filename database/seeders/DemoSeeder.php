<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\ExecutionAssessmentManager;
use App\Services\ScenarioExecutionManager;
use App\Services\ScenarioTemplateManager;
use App\Services\ScenarioVersionManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class DemoSeeder extends Seeder
{
    public const ORGANIZATION_NAME = 'Centro Aurora de Simulação Integrada';

    public const MANAGER_EMAIL = 'demo.manager@example.test';

    public const MANAGER_PASSWORD = 'Demo-M5-2026!';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('DemoSeeder cannot run in production.');
        }

        if (Organization::query()->where('name', self::ORGANIZATION_NAME)->exists()) {
            return;
        }

        DB::transaction(function (): void {
            $organization = Organization::create([
                'name' => self::ORGANIZATION_NAME,
                'kind' => 'training_center',
                'status' => 'active',
                'notes' => 'Dados integralmente fictícios para demonstração institucional do M5.',
            ]);

            $commandUnit = Unit::create([
                'organization_id' => $organization->id,
                'name' => 'Núcleo Alfa',
                'kind' => 'training_unit',
                'status' => 'active',
            ]);
            $responseUnit = Unit::create([
                'organization_id' => $organization->id,
                'name' => 'Núcleo Bravo',
                'kind' => 'training_unit',
                'status' => 'active',
            ]);

            $manager = User::create([
                'name' => 'Gestor Demo M5',
                'email' => self::MANAGER_EMAIL,
                'password' => self::MANAGER_PASSWORD,
                'status' => 'active',
            ]);

            UserOrganizationAccess::create([
                'user_id' => $manager->id,
                'organization_id' => $organization->id,
                'role' => 'demo_manager',
                'abilities' => AccessAbility::all(),
                'granted_at' => now(),
            ]);

            [$leader, $leaderMembership] = $this->createDemoPerson(
                $organization,
                $commandUnit,
                $manager,
                'Cap. Helena Duarte (fictícia)',
                'Coordenação de exercício',
            );
            [$operator, $operatorMembership] = $this->createDemoPerson(
                $organization,
                $responseUnit,
                $manager,
                'Sgt. Rafael Nogueira (fictício)',
                'Equipe de resposta',
            );

            $versionManager = app(ScenarioVersionManager::class);
            $executionManager = app(ScenarioExecutionManager::class);
            $assessmentManager = app(ExecutionAssessmentManager::class);
            $templateManager = app(ScenarioTemplateManager::class);

            $incidentVersion = $this->createPublishedScenario(
                $organization,
                $versionManager,
                'Incidente Multivítimas — Estação Aurora',
                'Terminal ferroviário simulado',
                'explosão simulada com colapso parcial',
                48,
                ['Rádio', 'IFAK', 'Kit de triagem', 'Maca'],
                [
                    'Estabelecer comando e controle com comunicação objetiva',
                    'Priorizar triagem e evacuação conforme gravidade observada',
                    'Manter rastreabilidade das decisões críticas',
                ],
                [
                    'Estabelecer zona segura e cadeia de comando',
                    'Executar triagem inicial e definir prioridades',
                    'Coordenar recursos e registrar eventos relevantes',
                ],
                [
                    'Entrada em área não liberada',
                    'Atraso crítico na identificação de hemorragia grave',
                ],
            );
            $incidentVersion->victims()->create([
                'code' => 'V-AUR-01',
                'profile' => ['age_group' => 'adult', 'mobility' => 'non_ambulatory'],
                'injuries' => ['severe_hemorrhage'],
                'initial_state' => ['conscious' => true, 'breathing' => true],
                'expected_priority' => 'immediate',
            ]);
            $incidentVersion->cohorts()->create([
                'label' => 'Vítimas deambulantes',
                'quantity' => 20,
                'profile' => ['mobility' => 'walking'],
                'triage_category' => 'minor',
                'characteristics' => ['smoke_exposure'],
            ]);

            $templateManager->create(
                $incidentVersion,
                $organization->id,
                $manager,
                'Template — Incidente Multivítimas',
                'Base fictícia reutilizável para demonstrações de triagem, comando e controle.',
            );

            $completedExecution = $executionManager->create($incidentVersion);
            $this->attachParticipant($completedExecution, $leader, $leaderMembership, 'Líder do exercício');
            $this->attachParticipant($completedExecution, $operator, $operatorMembership, 'Resposta operacional');
            $completedExecution = $executionManager->start($completedExecution);
            $decisionEvent = $completedExecution->events()->create([
                'kind' => 'observation',
                'occurred_at' => now(),
                'summary' => 'Equipe estabeleceu comando, iniciou triagem e comunicou prioridades.',
                'metadata' => ['source' => 'observer'],
            ]);
            $completedExecution = $executionManager->complete($completedExecution);

            $assessment = $assessmentManager->createForExecution($completedExecution);
            $scores = [92, 88, 90];
            foreach ($assessment->criteria()->get() as $index => $criterion) {
                $criterion->update([
                    'score' => $scores[$index] ?? 90,
                    'evaluator_notes' => 'Observação fictícia estruturada para demonstração.',
                ]);
                $criterion->evidence()->create([
                    'execution_event_id' => $decisionEvent->id,
                    'statement' => 'Evidência fictícia vinculada à timeline institucional do exercício.',
                    'observed_at' => $decisionEvent->occurred_at,
                    'created_by_user_id' => $manager->id,
                ]);
            }

            $debrief = $assessment->debrief()->create();
            $debrief->entries()->createMany([
                [
                    'kind' => 'fact',
                    'content' => 'A cadeia de comando foi estabelecida e a triagem começou no primeiro ciclo operacional.',
                    'position' => 1,
                    'created_by_user_id' => $manager->id,
                ],
                [
                    'kind' => 'interpretation',
                    'content' => 'A comunicação objetiva favoreceu a priorização, mas a distribuição de recursos pode ser antecipada.',
                    'position' => 2,
                    'created_by_user_id' => $manager->id,
                ],
                [
                    'kind' => 'recommendation',
                    'content' => 'Repetir o cenário com pressão temporal maior e redistribuição precoce dos recursos simulados.',
                    'position' => 3,
                    'created_by_user_id' => $manager->id,
                ],
            ]);
            $debrief->actionItems()->create([
                'action' => 'Executar nova rodada de treinamento focada em alocação antecipada de recursos.',
                'responsible_label' => 'Coordenação Demo M5',
                'due_date' => now()->addDays(30)->toDateString(),
                'notes' => 'Item inteiramente fictício criado pelo DemoSeeder.',
            ]);
            $assessmentManager->finalize($assessment, $manager);

            $commandVersion = $this->createPublishedScenario(
                $organization,
                $versionManager,
                'Ameaça Ativa — Complexo Horizonte',
                'Complexo administrativo simulado',
                'ameaça ativa simulada',
                12,
                ['Rádio', 'IFAK'],
                [
                    'Coordenar resposta inicial e comunicação interequipes',
                    'Preservar segurança durante progressão e atendimento',
                ],
                [
                    'Definir comando inicial',
                    'Comunicar setores e prioridades',
                ],
                [
                    'Progressão sem coordenação',
                    'Falha de comunicação entre setores',
                ],
            );
            $runningExecution = $executionManager->start($executionManager->create($commandVersion));
            $runningExecution->events()->create([
                'kind' => 'system',
                'occurred_at' => now(),
                'summary' => 'Execução demo em andamento para alimentar a fila operacional.',
                'metadata' => ['source' => 'system'],
            ]);

            $this->createPublishedScenario(
                $organization,
                $versionManager,
                'Evacuação Técnica — Edifício Boreal',
                'Edifício comercial simulado',
                'incêndio estrutural simulado',
                30,
                ['Rádio', 'Kit de triagem', 'Maca'],
                [
                    'Organizar evacuação por prioridade',
                    'Integrar controle de acesso e área de atendimento',
                ],
                [
                    'Definir rotas seguras',
                    'Estabelecer área de concentração de vítimas',
                ],
                [
                    'Uso de rota não liberada',
                    'Perda de contabilização de evacuados',
                ],
            );
        });
    }

    private function createPublishedScenario(
        Organization $organization,
        ScenarioVersionManager $versionManager,
        string $title,
        string $environment,
        string $mechanism,
        int $casualties,
        array $resources,
        array $objectives,
        array $actions,
        array $criticalErrors,
    ): ScenarioVersion {
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => $environment,
            'threat_level' => 'controlada',
            'casualties' => $casualties,
            'estimated_casualty_count' => $casualties,
            'mechanism' => $mechanism,
            'resources' => $resources,
            'learning_objectives' => $objectives,
            'expected_actions' => $actions,
            'critical_errors' => $criticalErrors,
            'status' => 'draft',
        ]);

        $version = $scenario->versions()->create([
            'version_number' => 1,
            'environment' => $environment,
            'threat_level' => 'controlada',
            'mechanism' => $mechanism,
            'estimated_casualty_count' => $casualties,
            'resources' => $resources,
            'learning_objectives' => $objectives,
            'expected_actions' => $actions,
            'critical_errors' => $criticalErrors,
            'publication_status' => 'draft',
        ]);

        return $versionManager->publish($version);
    }

    private function createDemoPerson(
        Organization $organization,
        Unit $unit,
        User $creator,
        string $name,
        string $position,
    ): array {
        $person = Person::create([
            'display_name' => $name,
            'status' => 'active',
            'created_by_user_id' => $creator->id,
        ]);
        $membership = OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'position' => $position,
            'started_at' => '2026-01-01',
            'status' => 'active',
        ]);

        return [$person, $membership];
    }

    private function attachParticipant(
        ScenarioExecution $execution,
        Person $person,
        OrganizationMembership $membership,
        string $role,
    ): void {
        $unit = $membership->unit()->first();

        $execution->participants()->create([
            'person_id' => $person->id,
            'organization_membership_id' => $membership->id,
            'unit_id_snapshot' => $membership->unit_id,
            'unit_name_snapshot' => $unit?->name,
            'position_snapshot' => $membership->position,
            'role' => $role,
        ]);
    }
}
