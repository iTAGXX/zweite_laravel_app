<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Person;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule as ValidationRuleFactory;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

trait PersonValidationRules
{
    /**
     * @return array<string, list<Rule|ValidationRule|Exists|Unique|string>>
     */
    protected function personRules(?Person $person = null): array
    {
        $organizationId = EnsureActiveOrganization::id();

        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:255'],
            'postalCode' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'dateOfBirth' => ['nullable', 'date', 'before:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'userId' => [
                'nullable',
                'integer',
                ValidationRuleFactory::exists('organization_memberships', 'user_id')
                    ->where(fn (Builder $query): Builder => $query->where('organization_id', $organizationId)),
                ValidationRuleFactory::unique('people', 'user_id')
                    ->where(fn (Builder $query): Builder => $query->where('organization_id', $organizationId))
                    ->ignore($person?->id),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{
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
     * }
     */
    protected function personPayload(array $validated): array
    {
        return [
            'first_name' => $validated['firstName'],
            'last_name' => $validated['lastName'],
            'email' => $this->blankToNull($validated['email'] ?? null),
            'phone' => $this->blankToNull($validated['phone'] ?? null),
            'street' => $this->blankToNull($validated['street'] ?? null),
            'postal_code' => $this->blankToNull($validated['postalCode'] ?? null),
            'city' => $this->blankToNull($validated['city'] ?? null),
            'country' => $this->blankToNull($validated['country'] ?? null),
            'date_of_birth' => $this->blankToNull($validated['dateOfBirth'] ?? null),
            'notes' => $this->blankToNull($validated['notes'] ?? null),
            'user_id' => $this->nullableInteger($validated['userId'] ?? null),
        ];
    }

    private function blankToNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
