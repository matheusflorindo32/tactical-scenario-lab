<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Database\Seeder;
use LogicException;

class R1BrowserSeeder extends Seeder
{
    private const VIEWER_EMAIL = 'demo.viewer@example.test';

    private const VIEWER_PASSWORD = 'Demo-R1-Viewer-2026!';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('R1BrowserSeeder is forbidden in production.');
        }

        $this->call(DemoSeeder::class);

        $organization = Organization::query()
            ->where('name', DemoSeeder::ORGANIZATION_NAME)
            ->firstOrFail();

        $viewer = User::query()->updateOrCreate(
            ['email' => self::VIEWER_EMAIL],
            [
                'name' => 'Demo Viewer',
                'password' => self::VIEWER_PASSWORD,
                'status' => 'active',
            ],
        );

        UserOrganizationAccess::query()->updateOrCreate(
            [
                'user_id' => $viewer->id,
                'organization_id' => $organization->id,
                'role' => 'viewer',
            ],
            [
                'abilities' => [AccessAbility::SCENARIOS_VIEW],
                'granted_at' => now(),
                'expires_at' => null,
                'revoked_at' => null,
            ],
        );
    }
}
