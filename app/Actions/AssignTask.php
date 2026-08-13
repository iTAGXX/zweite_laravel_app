<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Person;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AssignTask
{
    public function handle(User $actor, Task $task, ?Person $assignee): Task
    {
        Gate::forUser($actor)->authorize('assign', $task);

        if ($assignee !== null) {
            abort_unless($assignee->organization_id === $task->organization_id, 404);
        }

        $task->assignTo($assignee);

        return $task->refresh();
    }
}
