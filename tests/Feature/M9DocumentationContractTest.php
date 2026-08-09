<?php

namespace Tests\Feature;

use Tests\TestCase;

class M9DocumentationContractTest extends TestCase
{
    public function test_release_and_changelog_artifacts_exist(): void
    {
        $this->assertFileExists(base_path('docs/RELEASE.md'));
        $this->assertFileExists(base_path('CHANGELOG.md'));
    }

    public function test_release_runbook_covers_controlled_release_and_recovery_boundaries(): void
    {
        if (! file_exists(base_path('docs/RELEASE.md'))) {
            $this->markTestIncomplete('docs/RELEASE.md is not implemented yet.');
        }

        $release = mb_strtolower(file_get_contents(base_path('docs/RELEASE.md')));

        foreach ([
            'production:preflight',
            'migrate --force',
            '/health/live',
            '/health/ready',
            'application rollback',
            'schema rollback',
            'pitr',
            'migration identity',
            'runtime identity',
            'release sha',
            'traffic admission',
        ] as $contract) {
            $this->assertStringContainsString($contract, $release, "Missing release/recovery contract: {$contract}");
        }
    }

    public function test_changelog_is_milestone_based_without_fabricated_semantic_version(): void
    {
        if (! file_exists(base_path('CHANGELOG.md'))) {
            $this->markTestIncomplete('CHANGELOG.md is not implemented yet.');
        }

        $changelog = file_get_contents(base_path('CHANGELOG.md'));

        foreach (range(1, 9) as $milestone) {
            $this->assertStringContainsString("M{$milestone}", $changelog);
        }

        $this->assertStringNotContainsString('v1.0.0', $changelog);
    }

    public function test_release_docs_link_to_the_current_operational_contracts(): void
    {
        $readme = file_get_contents(base_path('README.md'));
        $production = mb_strtolower(file_get_contents(base_path('docs/PRODUCTION.md')));
        $security = mb_strtolower(file_get_contents(base_path('SECURITY.md')));

        $this->assertStringContainsString('docs/RELEASE.md', $readme);
        $this->assertStringContainsString('CHANGELOG.md', $readme);

        foreach ([
            'container',
            'pdo_pgsql',
            'non-root',
            'migration identity',
            'runtime identity',
            '/health/live',
            '/health/ready',
        ] as $contract) {
            $this->assertStringContainsString($contract, $production, "Production docs missing: {$contract}");
        }

        $this->assertStringContainsString('docs/production.md', $security);
        $this->assertStringNotContainsString('autenticação (planejada', $security);
    }
}
