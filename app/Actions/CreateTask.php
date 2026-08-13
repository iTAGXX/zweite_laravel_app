<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\TaskValidationRules;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CreateTask
{
    use TaskValidationRules;

    /**
     * @param  array{
     *     title: string,
     *     description: string|null,
     *     assignee_id: string|null,
     *     due_date: string|null,
     *     priority: string,
     *     status: string,
     *     linked_person_ids: list<string>
     * }  $attributes
     */
    public function handle(User $actor, array $attributes): Task
    {
        Gate::forUser($actor)->authorize('create', Task::class);

        $organizationId = EnsureActiveOrganization::id();

        abort_unless(is_string($organizationId), 403);

        $linkedPersonIds = $attributes['linked_person_ids'];
        unset($attributes['linked_person_ids']);

        $task = Task::query()->create([
            ...$attributes,
            'organization_id' => $organizationId,
            'priority' => TaskPriority::from($attributes['priority']),
            'status' => TaskStatus::from($attributes['status']),
            'completed_at' => TaskStatus::from($attributes['status']) === TaskStatus::Done ? now() : null,
        ]);

        $task->syncPersonLinks($linkedPersonIds);

        return $task->refresh();
    }
}
