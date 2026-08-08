<?php

namespace App\Support\Auth;

final class AccessAbility
{
    public const PEOPLE_VIEW = 'people.view';

    public const PEOPLE_MANAGE = 'people.manage';

    public const SCENARIOS_VIEW = 'scenarios.view';

    public const SCENARIOS_MANAGE = 'scenarios.manage';

    public const EVALUATIONS_MANAGE = 'evaluations.manage';

    public const REPORTS_VIEW = 'reports.view';

    public const ACCESS_MANAGE = 'access.manage';

    public static function all(): array
    {
        return [
            self::PEOPLE_VIEW,
            self::PEOPLE_MANAGE,
            self::SCENARIOS_VIEW,
            self::SCENARIOS_MANAGE,
            self::EVALUATIONS_MANAGE,
            self::REPORTS_VIEW,
            self::ACCESS_MANAGE,
        ];
    }

    public static function labels(): array
    {
        return [
            self::PEOPLE_VIEW => 'Visualizar pessoas',
            self::PEOPLE_MANAGE => 'Gerenciar pessoas',
            self::SCENARIOS_VIEW => 'Visualizar cenários',
            self::SCENARIOS_MANAGE => 'Gerenciar cenários',
            self::EVALUATIONS_MANAGE => 'Gerenciar avaliações',
            self::REPORTS_VIEW => 'Visualizar relatórios',
            self::ACCESS_MANAGE => 'Gerenciar acessos institucionais',
        ];
    }

    public static function isKnown(string $ability): bool
    {
        return in_array($ability, self::all(), true);
    }
}
