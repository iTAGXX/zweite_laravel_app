<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\TaskRecurrenceValidationRules;
use App\Enums\RecurrenceFrequency;
use App\Enums\TaskPriority;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\TaskRecurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;

class CreateTaskRecurrence
{
    use TaskRecurrenceValidationRules;

    public function __construct(private RecurrenceCalculator $calculator) {}

    /**
     * @param  array{
     *     title: string,
     *     description: string|null,
     *     assignee_id: string|null,
     *     priority: string,
     *     frequency: string,
     *     day_of_month: int,
     *     month: int|null,
     *     starts_on: string,
     *     ends_on: string|null
     * }  $attributes
     */
    public function handle(User $actor, array $attributes): TaskRecurrence
    {
        Gate::forUser($actor)->authorize('create', TaskRecurrence::class);

        $organizationId = EnsureActiveOrganization::id();

        abort_unless(is_string($organizationId), 403);

        $frequency = RecurrenceFrequency::from($attributes['frequency']);
        $nextOccurrenceOn = $this->calculator->occurrenceOnOrAfter(
            $frequency,
            CarbonImmutable::parse($attributes['starts_on']),
            $attributes['day_of_month'],
            $attributes['month'],
        );

        return TaskRecurrence::query()->create([
            'organization_id' => $organizationId,
            'title' => $attributes['title'],
            'description' => $attributes['description'],
            'assignee_id' => $attributes['assignee_id'],
            'priority' => TaskPriority::from($attributes['priority']),
            'frequency' => $frequency,
            'day_of_month' => $attributes['day_of_month'],
            'month' => $attributes['month'],
            'next_occurrence_on' => $nextOccurrenceOn->toDateString(),
            'ends_on' => $attributes['ends_on'],
            'paused_at' => null,
        ]);
    }
}
