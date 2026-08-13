<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RecurrenceFrequency;
use App\Enums\TaskPriority;
use App\Models\Organization;
use App\Models\Person;
use App\Models\TaskRecurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskRecurrence>
 */
class TaskRecurrenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dayOfMonth = fake()->numberBetween(1, 28);

        return [
            'organization_id' => Organization::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'assignee_id' => null,
            'priority' => TaskPriority::Medium,
            'frequency' => RecurrenceFrequency::Monthly,
            'day_of_month' => $dayOfMonth,
            'month' => null,
            'next_occurrence_on' => now()->toDateString(),
            'ends_on' => null,
            'paused_at' => null,
        ];
    }

    public function monthly(int $dayOfMonth = 15): static
    {
        return $this->state(fn (array $attributes): array => [
            'frequency' => RecurrenceFrequency::Monthly,
            'day_of_month' => $dayOfMonth,
            'month' => null,
        ]);
    }

    public function yearly(int $month = 3, int $dayOfMonth = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'frequency' => RecurrenceFrequency::Yearly,
            'month' => $month,
            'day_of_month' => $dayOfMonth,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes): array => [
            'paused_at' => now(),
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ends_on' => now()->subDay()->toDateString(),
            'next_occurrence_on' => now()->toDateString(),
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
