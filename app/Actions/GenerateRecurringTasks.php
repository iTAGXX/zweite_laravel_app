<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Scopes\BelongsToOrganizationScope;
use App\Models\Task;
use App\Models\TaskRecurrence;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GenerateRecurringTasks
{
    public function __construct(private RecurrenceCalculator $calculator) {}

    public function handle(?CarbonInterface $on = null): void
    {
        $on = CarbonImmutable::parse(($on ?? now())->toDateString());

        TaskRecurrence::queryDueAcrossOrganizations($on)
            ->orderBy('id')
            ->lazyById()
            ->each(function (TaskRecurrence $recurrence) use ($on): void {
                try {
                    $this->generateFor($recurrence, $on);
                } catch (Throwable $exception) {
                    Log::error('Recurring task generation failed for recurrence', [
                        'recurrence_id' => $recurrence->id,
                        'organization_id' => $recurrence->organization_id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });
    }

    private function generateFor(TaskRecurrence $recurrence, CarbonImmutable $on): void
    {
        DB::transaction(function () use ($recurrence, $on): void {
            $locked = TaskRecurrence::query()
                ->withoutGlobalScope(BelongsToOrganizationScope::class)
                ->whereKey($recurrence->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return;
            }

            Context::add(EnsureActiveOrganization::SESSION_KEY, $locked->organization_id);

            try {
                while ($locked->isDueOn($on)) {
                    $this->createOccurrence($locked);

                    $current = $locked->next_occurrence_on;
                    $next = $this->calculator->nextOccurrence(
                        $locked->frequency,
                        $current,
                        $locked->day_of_month,
                        $locked->month,
                    );

                    if ($next->lessThanOrEqualTo($current)) {
                        throw new RuntimeException('Recurrence next occurrence did not advance.');
                    }

                    $locked->update([
                        'next_occurrence_on' => $next->toDateString(),
                    ]);
                }
            } finally {
                Context::forget(EnsureActiveOrganization::SESSION_KEY);
            }
        });
    }

    private function createOccurrence(TaskRecurrence $recurrence): void
    {
        $occurrenceOn = $recurrence->next_occurrence_on;

        try {
            Task::query()->create([
                'organization_id' => $recurrence->organization_id,
                'recurrence_id' => $recurrence->id,
                'occurrence_key' => $recurrence->occurrenceKey($occurrenceOn),
                'title' => $recurrence->title,
                'description' => $recurrence->description,
                'assignee_id' => $recurrence->assignee_id,
                'due_date' => $occurrenceOn->toDateString(),
                'priority' => $recurrence->priority,
                'status' => TaskStatus::Open,
                'completed_at' => null,
            ]);
        } catch (UniqueConstraintViolationException) {
        }
    }
}
