<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateRecurringTasks;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateDueRecurringTasks implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function uniqueId(): string
    {
        return 'generate-due-recurring-tasks';
    }

    public function handle(GenerateRecurringTasks $generateRecurringTasks): void
    {
        $generateRecurringTasks->handle();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Recurring task generation job failed', [
            'message' => $exception?->getMessage(),
        ]);
    }
}
