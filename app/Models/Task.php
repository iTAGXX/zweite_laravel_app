<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskLinkableType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
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
 * @property Carbon|null $due_date
 * @property TaskPriority $priority
 * @property TaskStatus $status
 * @property Carbon|null $completed_at
 * @property string|null $recurrence_id
 * @property string|null $occurrence_key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'organization_id',
    'title',
    'description',
    'assignee_id',
    'due_date',
    'priority',
    'status',
    'completed_at',
    'recurrence_id',
    'occurrence_key',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'priority' => 'medium',
        'status' => 'open',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'completed_at' => 'datetime',
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
     * @return BelongsTo<TaskRecurrence, $this>
     */
    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(TaskRecurrence::class, 'recurrence_id');
    }

    /**
     * @return HasMany<TaskLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(TaskLink::class);
    }

    public function isDone(): bool
    {
        return $this->status === TaskStatus::Done;
    }

    public function markCompleted(): void
    {
        if ($this->isDone()) {
            return;
        }

        $this->update([
            'status' => TaskStatus::Done,
            'completed_at' => now(),
        ]);
    }

    public function assignTo(?Person $person): void
    {
        $this->update(['assignee_id' => $person?->id]);
    }

    /**
     * @param  list<string>  $personIds
     */
    public function syncPersonLinks(array $personIds): void
    {
        $personIds = array_values(array_unique($personIds));

        $existing = $this->links()
            ->where('linkable_type', TaskLinkableType::Person)
            ->get();

        foreach ($existing as $link) {
            if (! in_array($link->linkable_id, $personIds, true)) {
                $link->delete();
            }
        }

        $existingIds = $existing->pluck('linkable_id')->all();

        foreach ($personIds as $personId) {
            if (in_array($personId, $existingIds, true)) {
                continue;
            }

            $this->links()->create([
                'organization_id' => $this->organization_id,
                'linkable_type' => TaskLinkableType::Person,
                'linkable_id' => $personId,
            ]);
        }
    }

    /**
     * @param  Builder<Task>  $query
     */
    #[Scope]
    protected function statusFilter(Builder $query, string $status): void
    {
        match ($status) {
            TaskStatus::Open->value => $query->where('status', TaskStatus::Open),
            TaskStatus::InProgress->value => $query->where('status', TaskStatus::InProgress),
            TaskStatus::Done->value => $query->where('status', TaskStatus::Done),
            'all' => null,
            default => $query->where('status', '!=', TaskStatus::Done),
        };
    }

    /**
     * @param  Builder<Task>  $query
     */
    #[Scope]
    protected function assigneeFilter(Builder $query, string $assignee): void
    {
        match (true) {
            $assignee === 'all', $assignee === '' => null,
            $assignee === 'unassigned' => $query->whereNull('assignee_id'),
            default => $query->where('assignee_id', $assignee),
        };
    }
}
