<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

test('up() is idempotent when columns already exist', function () {
    expect(Schema::hasColumn('organizations', 'type'))->toBeTrue()
        ->and(Schema::hasColumn('organizations', 'enabled_modules'))->toBeTrue();

    $migration = require database_path('migrations/2026_08_13_151431_add_type_and_enabled_modules_to_organizations_table.php');

    expect(fn () => $migration->up())->not->toThrow(Throwable::class);

    expect(Schema::hasColumn('organizations', 'type'))->toBeTrue()
        ->and(Schema::hasColumn('organizations', 'enabled_modules'))->toBeTrue();
});

test('up() adds columns when missing and fills existing null rows', function () {
    Schema::table('organizations', function ($table) {
        $table->dropColumn(['type', 'enabled_modules']);
    });

    expect(Schema::hasColumn('organizations', 'type'))->toBeFalse()
        ->and(Schema::hasColumn('organizations', 'enabled_modules'))->toBeFalse();

    // Insert a row that will have enabled_modules = NULL after the column is added
    DB::table('organizations')->insert([
        'id' => (string) Str::uuid(),
        'name' => 'Test Org',
        'slug' => 'test-org',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_08_13_151431_add_type_and_enabled_modules_to_organizations_table.php');
    $migration->up();

    expect(Schema::hasColumn('organizations', 'type'))->toBeTrue()
        ->and(Schema::hasColumn('organizations', 'enabled_modules'))->toBeTrue();

    $org = DB::table('organizations')->first();
    expect($org->enabled_modules)->toBe('["club","stable"]');
});
