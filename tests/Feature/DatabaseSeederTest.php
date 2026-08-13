<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

test('organizations and memberships tables exist', function () {
    expect(Schema::hasTable('organizations'))->toBeTrue()
        ->and(Schema::hasTable('organization_memberships'))->toBeTrue();
});

test('organizations use uuid primary keys', function () {
    $organization = Organization::factory()->create();

    expect($organization->id)->toBeString()
        ->and(Str::isUuid($organization->id))->toBeTrue();
});

test('database seeder creates a demo organization and admin membership', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::query()->where('email', DatabaseSeeder::DEMO_ADMIN_EMAIL)->first();
    $organization = Organization::query()->where('slug', DatabaseSeeder::DEMO_ORGANIZATION_SLUG)->first();

    expect($user)->not->toBeNull()
        ->and($organization)->not->toBeNull();

    $this->assertModelExists($user);
    $this->assertModelExists($organization);

    expect(OrganizationMembership::query()->where('user_id', $user->id)->where('organization_id', $organization->id)->count())->toBe(1);

    $membership = OrganizationMembership::query()
        ->where('user_id', $user->id)
        ->where('organization_id', $organization->id)
        ->firstOrFail();

    expect($membership->role?->slug)->toBe('admin');
});

test('database seeder is idempotent', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', DatabaseSeeder::DEMO_ADMIN_EMAIL)->count())->toBe(1)
        ->and(Organization::query()->where('slug', DatabaseSeeder::DEMO_ORGANIZATION_SLUG)->count())->toBe(1)
        ->and(OrganizationMembership::query()->count())->toBe(1);
});
