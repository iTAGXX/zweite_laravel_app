<?php

use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Invitation;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'verified', 'tenant'])
        ->get('/__tenant-probe/invitations/{invitation}', function (Invitation $invitation) {
            return response()->noContent();
        });
});

test('invitations use the belongs to organization scope', function () {
    expect(class_uses_recursive(Invitation::class))->toContain(BelongsToOrganization::class)
        ->and(class_uses_recursive(OrganizationMembership::class))->not->toContain(BelongsToOrganization::class);
});

test('queries without an active organization return no tenant rows', function () {
    Invitation::factory()->create();

    EnsureActiveOrganization::forget();

    expect(Invitation::query()->count())->toBe(0);
});

test('a user cannot read another organizations invitation via eloquent', function () {
    $userA = User::factory()->withOrganization()->create();
    $organizationA = $userA->organizations()->firstOrFail();
    $invitationA = Invitation::factory()->for($organizationA)->create();

    $userB = User::factory()->withOrganization()->create();
    $organizationB = $userB->organizations()->firstOrFail();
    $invitationB = Invitation::factory()->for($organizationB)->create();

    setActiveOrganization($organizationA->id);

    expect(Invitation::query()->find($invitationA->id))->not->toBeNull()
        ->and(Invitation::query()->find($invitationB->id))->toBeNull()
        ->and(Invitation::query()->count())->toBe(1);
});

test('a foreign invitation id returns 404 without leaking data', function () {
    $userA = User::factory()->withOrganization()->create();
    $organizationA = $userA->organizations()->firstOrFail();
    $invitationA = Invitation::factory()->for($organizationA)->create();

    $userB = User::factory()->withOrganization()->create();
    $organizationB = $userB->organizations()->firstOrFail();
    $invitationB = Invitation::factory()->for($organizationB)->create();

    $this->actingAs($userA)
        ->get('/__tenant-probe/invitations/'.$invitationB->id)
        ->assertNotFound()
        ->assertDontSee($invitationB->email);

    $this->actingAs($userA)
        ->get('/__tenant-probe/invitations/'.$invitationA->id)
        ->assertNoContent();
});
