<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CompleteTask
{
    public function handle(User $actor, Task $task): Task
    {
        Gate::forUser($actor)->authorize('complete', $task);

        $task->markCompleted();

        return $task->refresh();
    }
}
