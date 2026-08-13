<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationMembership>
 */
class OrganizationMembershipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'role_id' => Role::factory(),
        ];
    }

    public function forRole(RoleName $role): static
    {
        return $this->state(function () use ($role): array {
            $record = Role::query()->where('slug', $role->value)->first()
                ?? Role::factory()->create([
                    'name' => $role->name,
                    'slug' => $role->value,
                ]);

            return ['role_id' => $record->id];
        });
    }
}
