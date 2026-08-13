<?php

declare(strict_types=1);

namespace App\Enums;

enum RecurrenceFrequency: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => __('Monthly'),
            self::Yearly => __('Yearly'),
        };
    }
}
