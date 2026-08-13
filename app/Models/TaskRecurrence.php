<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecurrenceFrequency;
use App\Enums\TaskPriority;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Scopes\BelongsToOrganizationScope;
use Carbon\CarbonInterface;
use Database\Factories\TaskRecurrenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $title
 * @property string|null $description
 * @property string|null $assignee_id
 * @property TaskPriority $priority
 * @property RecurrenceFrequency $frequency
 * @property int $day_of_month
 * @property int|null $month
 * @property Carbon $next_occurrence_on
 * @property Carbon|null $ends_on
 * @property Carbon|null $paused_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'organization_id',
    'title',
    'description',
    'assignee_id',
    'priority',
    'frequency',
    'day_of_month',
    'month',
    'next_occurrence_on',
    'ends_on',
    'paused_at',
])]
class TaskRecurrence extends Model
{
    /** @use HasFactory<TaskRecurrenceFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'priority' => 'medium',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'frequency' => RecurrenceFrequency::class,
            'day_of_month' => 'integer',
            'month' => 'integer',
            'next_occurrence_on' => 'date',
            'ends_on' => 'date',
            'paused_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'assignee_id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'recurrence_id');
    }

    public function isPaused(): bool
    {
        return $this->paused_at !== null;
    }

    public function isEnded(): bool
    {
        return $this->ends_on !== null
            && $this->next_occurrence_on->toDateString() > $this->ends_on->toDateString();
    }

    public function isDueOn(CarbonInterface $on): bool
    {
        if ($this->isPaused()) {
            return false;
        }

        $onDate = $on->toDateString();
        $nextDate = $this->next_occurrence_on->toDateString();

        if ($nextDate > $onDate) {
            return false;
        }

        if ($this->ends_on !== null && $nextDate > $this->ends_on->toDateString()) {
            return false;
        }

        return true;
    }

    public function occurrenceKey(CarbonInterface $on): string
    {
        return $this->id.':'.$on->toDateString();
    }

    /**
     * @return Builder<static>
     */
    public static function queryDueAcrossOrganizations(CarbonInterface $on): Builder
    {
        $date = $on->toDateString();

        return static::query()
            ->withoutGlobalScope(BelongsToOrganizationScope::class)
            ->whereNull('paused_at')
            ->whereDate('next_occurrence_on', '<=', $date)
            ->where(function (Builder $query): void {
                $query->whereNull('ends_on')
                    ->orWhereColumn('next_occurrence_on', '<=', 'ends_on');
            });
    }
}
