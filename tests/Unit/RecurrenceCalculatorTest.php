<?php

declare(strict_types=1);

use App\Actions\RecurrenceCalculator;
use App\Enums\RecurrenceFrequency;
use Carbon\CarbonImmutable;

test('monthly rules land on the same day next month', function () {
    $next = (new RecurrenceCalculator)->nextOccurrence(
        RecurrenceFrequency::Monthly,
        CarbonImmutable::parse('2026-08-13'),
        13,
    );

    expect($next->toDateString())->toBe('2026-09-13');
});

test('monthly rules clamp to the last day of shorter months', function () {
    $calculator = new RecurrenceCalculator;

    $february = $calculator->nextOccurrence(
        RecurrenceFrequency::Monthly,
        CarbonImmutable::parse('2026-01-31'),
        31,
    );
    $march = $calculator->nextOccurrence(
        RecurrenceFrequency::Monthly,
        $february,
        31,
    );

    expect($february->toDateString())->toBe('2026-02-28')
        ->and($march->toDateString())->toBe('2026-03-31');
});

test('monthly rules clamp to february 29 in a leap year', function () {
    $next = (new RecurrenceCalculator)->nextOccurrence(
        RecurrenceFrequency::Monthly,
        CarbonImmutable::parse('2028-01-31'),
        31,
    );

    expect($next->toDateString())->toBe('2028-02-29');
});

test('yearly rules keep month and day', function () {
    $next = (new RecurrenceCalculator)->nextOccurrence(
        RecurrenceFrequency::Yearly,
        CarbonImmutable::parse('2026-03-01'),
        1,
        3,
    );

    expect($next->toDateString())->toBe('2027-03-01');
});

test('yearly rules clamp day 29 of february outside leap years', function () {
    $next = (new RecurrenceCalculator)->nextOccurrence(
        RecurrenceFrequency::Yearly,
        CarbonImmutable::parse('2024-02-29'),
        29,
        2,
    );

    expect($next->toDateString())->toBe('2025-02-28');
});

test('the first monthly occurrence on or after a date stays in the same month when possible', function () {
    $onOrAfter = (new RecurrenceCalculator)->occurrenceOnOrAfter(
        RecurrenceFrequency::Monthly,
        CarbonImmutable::parse('2026-08-13'),
        15,
    );

    expect($onOrAfter->toDateString())->toBe('2026-08-15');
});

test('the first monthly occurrence on or after a date moves to next month when the day has passed', function () {
    $onOrAfter = (new RecurrenceCalculator)->occurrenceOnOrAfter(
        RecurrenceFrequency::Monthly,
        CarbonImmutable::parse('2026-08-13'),
        10,
    );

    expect($onOrAfter->toDateString())->toBe('2026-09-10');
});

test('the first yearly occurrence on or after a date uses the next year when the month has passed', function () {
    $onOrAfter = (new RecurrenceCalculator)->occurrenceOnOrAfter(
        RecurrenceFrequency::Yearly,
        CarbonImmutable::parse('2026-08-13'),
        1,
        3,
    );

    expect($onOrAfter->toDateString())->toBe('2027-03-01');
});

test('yearly rules require a month', function () {
    (new RecurrenceCalculator)->nextOccurrence(
        RecurrenceFrequency::Yearly,
        CarbonImmutable::parse('2026-08-13'),
        1,
    );
})->throws(InvalidArgumentException::class);
