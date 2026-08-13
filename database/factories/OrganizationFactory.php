<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'type' => OrganizationType::Mixed,
            'enabled_modules' => OrganizationType::Mixed->defaultModules(),
        ];
    }

    public function club(): static
    {
        return $this->state(fn (): array => [
            'type' => OrganizationType::Club,
            'enabled_modules' => OrganizationType::Club->defaultModules(),
        ]);
    }

    public function stable(): static
    {
        return $this->state(fn (): array => [
            'type' => OrganizationType::Stable,
            'enabled_modules' => OrganizationType::Stable->defaultModules(),
        ]);
    }

    public function mixed(): static
    {
        return $this->state(fn (): array => [
            'type' => OrganizationType::Mixed,
            'enabled_modules' => OrganizationType::Mixed->defaultModules(),
        ]);
    }
}
