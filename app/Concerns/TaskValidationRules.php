<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Middleware\EnsureActiveOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Exists;

trait TaskValidationRules
{
    /**
     * @return array<string, list<Enum|Exists|ValidationRule|string>>
     */
    protected function taskRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigneeId' => ['nullable', 'uuid', $this->personInOrganizationRule()],
            'dueDate' => ['nullable', 'date'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'linkedPersonIds' => ['nullable', 'array'],
            'linkedPersonIds.*' => ['uuid', $this->personInOrganizationRule()],
        ];
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    protected function quickCreateRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'dueDate' => ['nullable', 'date'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{
     *     title: string,
     *     description: string|null,
     *     assignee_id: string|null,
     *     due_date: string|null,
     *     priority: string,
     *     status: string,
     *     linked_person_ids: list<string>
     * }
     */
    protected function taskPayload(array $validated): array
    {
        return [
            'title' => $validated['title'],
            'description' => $this->blankToNull($validated['description'] ?? null),
            'assignee_id' => $this->blankToNull($validated['assigneeId'] ?? null),
            'due_date' => $this->blankToNull($validated['dueDate'] ?? null),
            'priority' => $validated['priority'] ?? TaskPriority::Medium->value,
            'status' => $validated['status'] ?? TaskStatus::Open->value,
            'linked_person_ids' => $this->stringList($validated['linkedPersonIds'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{
     *     title: string,
     *     description: string|null,
     *     assignee_id: string|null,
     *     due_date: string|null,
     *     priority: string,
     *     status: string,
     *     linked_person_ids: list<string>
     * }
     */
    protected function quickCreatePayload(array $validated): array
    {
        return [
            'title' => $validated['title'],
            'description' => null,
            'assignee_id' => null,
            'due_date' => $this->blankToNull($validated['dueDate'] ?? null),
            'priority' => TaskPriority::Medium->value,
            'status' => TaskStatus::Open->value,
            'linked_person_ids' => [],
        ];
    }

    private function personInOrganizationRule(): Exists
    {
        $organizationId = EnsureActiveOrganization::id();

        return Rule::exists('people', 'id')
            ->where(fn (Builder $query): Builder => $query->where('organization_id', $organizationId));
    }

    private function blankToNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $ids[] = $item;
            }
        }

        return array_values(array_unique($ids));
    }
}
