<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\PersonValidationRules;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdatePerson
{
    use PersonValidationRules;

    /**
     * @param  array{
     *     first_name: string,
     *     last_name: string,
     *     email: string|null,
     *     phone: string|null,
     *     street: string|null,
     *     postal_code: string|null,
     *     city: string|null,
     *     country: string|null,
     *     date_of_birth: string|null,
     *     notes: string|null,
     *     user_id: int|null
     * }  $attributes
     */
    public function handle(User $actor, Person $person, array $attributes): Person
    {
        Gate::forUser($actor)->authorize('update', $person);

        $person->update($attributes);

        return $person->refresh();
    }
}
