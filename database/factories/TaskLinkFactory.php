<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskLinkableType;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Task;
use App\Models\TaskLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskLink>
 */
class TaskLinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'task_id' => Task::factory()->for($organization),
            'linkable_type' => TaskLinkableType::Person,
            'linkable_id' => Person::factory()->for($organization),
        ];
    }

    public function forPerson(Person $person): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $person->organization_id,
            'linkable_type' => TaskLinkableType::Person,
            'linkable_id' => $person->id,
        ]);
    }
}
