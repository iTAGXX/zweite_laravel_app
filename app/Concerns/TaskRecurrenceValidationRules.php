<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\RecurrenceFrequency;
use App\Enums\TaskPriority;
use App\Http\Middleware\EnsureActiveOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Exists;

trait TaskRecurrenceValidationRules
{
    /**
     * @return array<string, list<Enum|Exists|ValidationRule|string>>
     */
    protected function recurrenceRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigneeId' => ['nullable', 'uuid', $this->recurrencePersonInOrganizationRule()],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'frequency' => ['required', Rule::enum(RecurrenceFrequency::class)],
            'dayOfMonth' => ['required', 'integer', 'min:1', 'max:31'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12', 'required_if:frequency,'.RecurrenceFrequency::Yearly->value],
            'startsOn' => ['required', 'date'],
            'endsOn' => ['nullable', 'date', 'after_or_equal:startsOn'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{
     *     title: string,
     *     description: string|null,
     *     assignee_id: string|null,
     *     priority: string,
     *     frequency: string,
     *     day_of_month: int,
     *     month: int|null,
     *     starts_on: string,
     *     ends_on: string|null
     * }
     */
    protected function recurrencePayload(array $validated): array
    {
        $frequency = RecurrenceFrequency::from($validated['frequency']);

        return [
            'title' => $validated['title'],
            'description' => $this->recurrenceBlankToNull($validated['description'] ?? null),
            'assignee_id' => $this->recurrenceBlankToNull($validated['assigneeId'] ?? null),
            'priority' => $validated['priority'],
            'frequency' => $frequency->value,
            'day_of_month' => (int) $validated['dayOfMonth'],
            'month' => $frequency === RecurrenceFrequency::Yearly ? (int) $validated['month'] : null,
            'starts_on' => $validated['startsOn'],
            'ends_on' => $this->recurrenceBlankToNull($validated['endsOn'] ?? null),
        ];
    }

    private function recurrencePersonInOrganizationRule(): Exists
    {
        $organizationId = EnsureActiveOrganization::id();

        return Rule::exists('people', 'id')
            ->where(fn (Builder $query): Builder => $query->where('organization_id', $organizationId));
    }

    private function recurrenceBlankToNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
