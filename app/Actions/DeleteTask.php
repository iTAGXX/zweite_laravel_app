<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteTask
{
    public function handle(User $actor, Task $task): void
    {
        Gate::forUser($actor)->authorize('delete', $task);

        $task->delete();
    }
}
