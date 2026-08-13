<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;

enum TaskLinkableType: string
{
    case Person = 'person';

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Person => Person::class,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Person => __('Person'),
        };
    }
}
