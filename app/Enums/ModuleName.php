<?php

declare(strict_types=1);

namespace App\Enums;

enum ModuleName: string
{
    case Club = 'club';
    case Stable = 'stable';

    public function label(): string
    {
        return match ($this) {
            self::Club => __('Club module'),
            self::Stable => __('Stable module'),
        };
    }
}
