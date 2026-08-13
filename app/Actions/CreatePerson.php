<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\PersonValidationRules;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CreatePerson
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
    public function handle(User $actor, array $attributes): Person
    {
        Gate::forUser($actor)->authorize('create', Person::class);

        $organizationId = EnsureActiveOrganization::id();

        abort_unless(is_string($organizationId), 403);

        return Person::query()->create([
            ...$attributes,
            'organization_id' => $organizationId,
        ]);
    }
}
