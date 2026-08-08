<?php

namespace App\Services;

use App\Models\Scenario;
use App\Models\ScenarioVersion;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class ScenarioVersionManager
{
    public function publish(ScenarioVersion $version): ScenarioVersion
    {
        if ($version->publication_status === 'published') {
            return $version;
        }

        if ($version->publication_status !== 'draft') {
            throw new LogicException('Only draft scenario versions can be published.');
        }

        $version->update(['publication_status' => 'published']);

        return $version->fresh();
    }

    public function revise(ScenarioVersion $source, array $overrides = []): ScenarioVersion
    {
        if ($source->publication_status !== 'published') {
            throw new LogicException('Only published scenario versions can be revised into a new version.');
        }

        $unknownFields = array_diff(array_keys($overrides), ScenarioVersion::DEFINITION_FIELDS);

        if ($unknownFields !== []) {
            throw new InvalidArgumentException('Unsupported scenario version override: '.implode(', ', $unknownFields));
        }

        return DB::transaction(function () use ($source, $overrides): ScenarioVersion {
            $scenario = Scenario::query()
                ->whereKey($source->scenario_id)
                ->lockForUpdate()
                ->firstOrFail();

            $nextVersionNumber = ((int) $scenario->versions()->max('version_number')) + 1;
            $definition = [];

            foreach (ScenarioVersion::DEFINITION_FIELDS as $field) {
                $definition[$field] = $source->getAttribute($field);
            }

            return $scenario->versions()->create([
                ...$definition,
                ...$overrides,
                'version_number' => $nextVersionNumber,
                'publication_status' => 'draft',
            ]);
        });
    }
}
