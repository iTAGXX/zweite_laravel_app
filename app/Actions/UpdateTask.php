<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\TaskValidationRules;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateTask
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
    public function handle(User $actor, Task $task, array $attributes): Task
    {
        Gate::forUser($actor)->authorize('update', $task);

        $linkedPersonIds = $attributes['linked_person_ids'];
        unset($attributes['linked_person_ids']);

        $status = TaskStatus::from($attributes['status']);

        $task->update([
            ...$attributes,
            'priority' => TaskPriority::from($attributes['priority']),
            'status' => $status,
            'completed_at' => $status === TaskStatus::Done
                ? ($task->completed_at ?? now())
                : null,
        ]);

        $task->syncPersonLinks($linkedPersonIds);

        return $task->refresh();
    }
}
