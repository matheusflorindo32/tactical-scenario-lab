<?php

namespace App\Services;

use App\Models\Scenario;
use App\Models\ScenarioTemplate;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Support\Auth\AccessAbility;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class ScenarioTemplateManager
{
    public function create(
        ScenarioVersion $version,
        int $organizationId,
        User $actor,
        string $name,
        ?string $description,
    ): ScenarioTemplate {
        $name = trim($name);
        $description = $description !== null ? trim($description) : null;
        $description = $description === '' ? null : $description;

        if ($name === '' || mb_strlen($name) > 150) {
            throw new InvalidArgumentException('Scenario template name must contain between 1 and 150 characters.');
        }

        $this->ensureActorCanManage($actor, $organizationId);

        return DB::transaction(function () use ($version, $organizationId, $actor, $name, $description): ScenarioTemplate {
            $source = ScenarioVersion::query()
                ->with('scenario')
                ->lockForUpdate()
                ->findOrFail($version->id);

            if ($source->scenario->organization_id !== $organizationId) {
                throw new LogicException('Scenario template source must belong to the active organization.');
            }

            if ($source->publication_status !== 'published') {
                throw new LogicException('Scenario templates require a published source version.');
            }

            return ScenarioTemplate::create([
                'organization_id' => $organizationId,
                'source_scenario_version_id' => $source->id,
                'name' => $name,
                'description' => $description,
                'status' => 'active',
                'created_by_user_id' => $actor->id,
            ]);
        });
    }

    public function use(ScenarioTemplate $template, User $actor): Scenario
    {
        return DB::transaction(function () use ($template, $actor): Scenario {
            $lockedTemplate = ScenarioTemplate::query()
                ->lockForUpdate()
                ->findOrFail($template->id);

            if ($lockedTemplate->isArchived()) {
                throw new LogicException('Archived scenario templates cannot be used.');
            }

            if (! $lockedTemplate->isActive()) {
                throw new LogicException('Scenario template is not active.');
            }

            $this->ensureActorCanManage($actor, $lockedTemplate->organization_id);

            $source = ScenarioVersion::query()
                ->with(['scenario', 'victims', 'cohorts'])
                ->lockForUpdate()
                ->findOrFail($lockedTemplate->source_scenario_version_id);

            if ($source->scenario->organization_id !== $lockedTemplate->organization_id) {
                throw new LogicException('Scenario template source must belong to the template organization.');
            }

            if ($source->publication_status !== 'published') {
                throw new LogicException('Scenario template source must remain published.');
            }

            $definition = $source->only(ScenarioVersion::DEFINITION_FIELDS);

            $scenario = Scenario::create([
                'organization_id' => $lockedTemplate->organization_id,
                'title' => $lockedTemplate->name,
                'environment' => $definition['environment'],
                'threat_level' => $definition['threat_level'],
                'casualties' => $definition['estimated_casualty_count'],
                'estimated_casualty_count' => $definition['estimated_casualty_count'],
                'mechanism' => $definition['mechanism'],
                'resources' => $definition['resources'],
                'learning_objectives' => $definition['learning_objectives'],
                'expected_actions' => $definition['expected_actions'],
                'critical_errors' => $definition['critical_errors'],
                'status' => 'draft',
            ]);

            $newVersion = $scenario->versions()->create([
                'version_number' => 1,
                ...$definition,
                'publication_status' => 'draft',
            ]);

            foreach ($source->victims as $victim) {
                $newVersion->victims()->create([
                    'code' => $victim->code,
                    'profile' => $victim->profile,
                    'injuries' => $victim->injuries,
                    'initial_state' => $victim->initial_state,
                    'expected_priority' => $victim->expected_priority,
                ]);
            }

            foreach ($source->cohorts as $cohort) {
                $newVersion->cohorts()->create([
                    'label' => $cohort->label,
                    'quantity' => $cohort->quantity,
                    'profile' => $cohort->profile,
                    'triage_category' => $cohort->triage_category,
                    'characteristics' => $cohort->characteristics,
                ]);
            }

            return $scenario->fresh();
        });
    }

    public function archive(ScenarioTemplate $template): ScenarioTemplate
    {
        return DB::transaction(function () use ($template): ScenarioTemplate {
            $lockedTemplate = ScenarioTemplate::query()
                ->lockForUpdate()
                ->findOrFail($template->id);

            if ($lockedTemplate->isArchived()) {
                return $lockedTemplate;
            }

            if (! $lockedTemplate->isActive()) {
                throw new LogicException('Only active scenario templates can be archived.');
            }

            $lockedTemplate->update(['status' => 'archived']);

            return $lockedTemplate->fresh();
        });
    }

    private function ensureActorCanManage(User $actor, int $organizationId): void
    {
        $access = $actor->activeOrganizationAccesses()
            ->where('organization_id', $organizationId)
            ->first();

        if (! $actor->isActive()
            || ! $access
            || ! in_array(AccessAbility::SCENARIOS_MANAGE, $access->abilities ?? [], true)) {
            throw new LogicException('Actor must have scenarios.manage in the template organization.');
        }
    }
}
