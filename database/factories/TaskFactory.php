<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'assignee_id' => null,
            'due_date' => fake()->boolean() ? fake()->dateTimeBetween('now', '+2 weeks')->format('Y-m-d') : null,
            'priority' => TaskPriority::Medium,
            'status' => TaskStatus::Open,
            'completed_at' => null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaskStatus::Done,
            'completed_at' => now(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaskStatus::InProgress,
        ]);
    }

    public function assignedTo(Person $person): static
    {
        return $this->state(fn (array $attributes): array => [
            'assignee_id' => $person->id,
            'organization_id' => $person->organization_id,
        ]);
    }
}
