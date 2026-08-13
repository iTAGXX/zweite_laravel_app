<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TaskRecurrence;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PauseTaskRecurrence
{
    public function handle(User $actor, TaskRecurrence $recurrence): TaskRecurrence
    {
        Gate::forUser($actor)->authorize('pause', $recurrence);

        if ($recurrence->paused_at === null) {
            $recurrence->update(['paused_at' => now()]);
        }

        return $recurrence->refresh();
    }
}
