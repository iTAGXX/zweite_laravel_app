<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TaskRecurrence;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class EndTaskRecurrence
{
    public function handle(User $actor, TaskRecurrence $recurrence): TaskRecurrence
    {
        Gate::forUser($actor)->authorize('end', $recurrence);

        $recurrence->update([
            'ends_on' => $recurrence->next_occurrence_on->subDay()->toDateString(),
        ]);

        return $recurrence->refresh();
    }
}
