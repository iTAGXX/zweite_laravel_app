<?php

declare(strict_types=1);

namespace App\Enums;

enum OrganizationType: string
{
    case Club = 'club';
    case Stable = 'stable';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Club => __('Club'),
            self::Stable => __('Stable'),
            self::Mixed => __('Mixed'),
        };
    }

    /**
     * @return list<ModuleName>
     */
    public function defaultModules(): array
    {
        return match ($this) {
            self::Club => [ModuleName::Club],
            self::Stable => [ModuleName::Stable],
            self::Mixed => [ModuleName::Club, ModuleName::Stable],
        };
    }
}
