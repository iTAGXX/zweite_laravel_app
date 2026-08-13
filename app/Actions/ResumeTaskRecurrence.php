<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TaskRecurrence;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ResumeTaskRecurrence
{
    public function handle(User $actor, TaskRecurrence $recurrence): TaskRecurrence
    {
        Gate::forUser($actor)->authorize('resume', $recurrence);

        if ($recurrence->paused_at !== null) {
            $recurrence->update(['paused_at' => null]);
        }

        return $recurrence->refresh();
    }
}
