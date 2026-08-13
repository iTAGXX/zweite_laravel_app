<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskLinkableType;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TaskLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $task_id
 * @property TaskLinkableType $linkable_type
 * @property string $linkable_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'organization_id',
    'task_id',
    'linkable_type',
    'linkable_id',
])]
class TaskLink extends Model
{
    /** @use HasFactory<TaskLinkFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'linkable_type' => TaskLinkableType::class,
        ];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function resolveLinkable(): ?Model
    {
        return $this->linkable_type->modelClass()::query()->find($this->linkable_id);
    }
}
