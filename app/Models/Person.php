<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $organization_id
 * @property int|null $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $street
 * @property string|null $postal_code
 * @property string|null $city
 * @property string|null $country
 * @property Carbon|null $date_of_birth
 * @property string|null $notes
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $display_name
 */
#[Fillable([
    'organization_id',
    'user_id',
    'first_name',
    'last_name',
    'email',
    'phone',
    'street',
    'postal_code',
    'city',
    'country',
    'date_of_birth',
    'notes',
    'archived_at',
])]
class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function archive(): void
    {
        if ($this->archived_at !== null) {
            return;
        }

        $this->update(['archived_at' => now()]);
    }

    public function unarchive(): void
    {
        if ($this->archived_at === null) {
            return;
        }

        $this->update(['archived_at' => null]);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => Str::trim($this->first_name.' '.$this->last_name));
    }

    /**
     * @param  Builder<Person>  $query
     */
    #[Scope]
    protected function notArchived(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<Person>  $query
     */
    #[Scope]
    protected function onlyArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    /**
     * @param  Builder<Person>  $query
     */
    #[Scope]
    protected function archivedStatus(Builder $query, string $status): void
    {
        match ($status) {
            'archived' => $query->whereNotNull('archived_at'),
            'all' => null,
            default => $query->whereNull('archived_at'),
        };
    }

    /**
     * @param  Builder<Person>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = Str::trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->whereLike('first_name', '%'.$term.'%')
                ->orWhereLike('last_name', '%'.$term.'%')
                ->orWhereLike('email', '%'.$term.'%')
                ->orWhereLike('phone', '%'.$term.'%');
        });
    }
}
