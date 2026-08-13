<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RecurrenceFrequency;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class RecurrenceCalculator
{
    public function occurrenceOnOrAfter(
        RecurrenceFrequency $frequency,
        CarbonInterface $from,
        int $dayOfMonth,
        ?int $month = null,
    ): CarbonImmutable {
        $this->assertDayOfMonth($dayOfMonth);

        $from = CarbonImmutable::parse($from->toDateString());

        if ($frequency === RecurrenceFrequency::Yearly) {
            $month = $this->requireMonth($month);
            $candidate = $this->dateFor($from->year, $month, $dayOfMonth);

            if ($candidate->lt($from)) {
                return $this->dateFor($from->year + 1, $month, $dayOfMonth);
            }

            return $candidate;
        }

        $candidate = $this->dateFor($from->year, $from->month, $dayOfMonth);

        if ($candidate->lt($from)) {
            return $this->nextOccurrence($frequency, $candidate, $dayOfMonth, $month);
        }

        return $candidate;
    }

    public function nextOccurrence(
        RecurrenceFrequency $frequency,
        CarbonInterface $from,
        int $dayOfMonth,
        ?int $month = null,
    ): CarbonImmutable {
        $this->assertDayOfMonth($dayOfMonth);

        $from = CarbonImmutable::parse($from->toDateString());

        if ($frequency === RecurrenceFrequency::Yearly) {
            $month = $this->requireMonth($month);

            return $this->dateFor($from->year + 1, $month, $dayOfMonth);
        }

        $year = $from->month === 12 ? $from->year + 1 : $from->year;
        $nextMonth = $from->month === 12 ? 1 : $from->month + 1;

        return $this->dateFor($year, $nextMonth, $dayOfMonth);
    }

    private function dateFor(int $year, int $month, int $dayOfMonth): CarbonImmutable
    {
        $lastDay = CarbonImmutable::createFromDate($year, $month, 1)->endOfMonth()->day;

        return CarbonImmutable::createFromDate($year, $month, min($dayOfMonth, $lastDay))->startOfDay();
    }

    private function requireMonth(?int $month): int
    {
        if ($month === null) {
            throw new InvalidArgumentException('Yearly recurrence requires a month.');
        }

        $this->assertMonth($month);

        return $month;
    }

    private function assertDayOfMonth(int $dayOfMonth): void
    {
        if ($dayOfMonth < 1 || $dayOfMonth > 31) {
            throw new InvalidArgumentException('Day of month must be between 1 and 31.');
        }
    }

    private function assertMonth(int $month): void
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Month must be between 1 and 12.');
        }
    }
}
