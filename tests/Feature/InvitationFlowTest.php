<?php

declare(strict_types=1);

use App\Actions\InviteMember;
use App\Enums\RoleName;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Mail\OrganizationInvitation;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

test('an admin can invite a member and mail is queued', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    $role = Role::query()->where('slug', RoleName::Staff->value)->firstOrFail();

    $this->actingAs($admin);
    setActiveOrganization($organization->id);

    Livewire::test('invite-members')
        ->set('email', 'new-member@example.com')
        ->set('roleId', (string) $role->id)
        ->call('sendInvitation')
        ->assertHasNoErrors();

    $invitation = Invitation::query()->where('email', 'new-member@example.com')->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation?->role_id)->toBe($role->id)
        ->and($invitation?->organization_id)->toBe($organization->id);

    Mail::assertQueued(OrganizationInvitation::class, function (OrganizationInvitation $mail): bool {
        return $mail->hasTo('new-member@example.com');
    });
});

test('staff cannot invite members', function () {
    $staff = User::factory()->withOrganization(RoleName::Staff)->create();
    $organization = $staff->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $this->actingAs($staff)
        ->get(route('members'))
        ->assertForbidden();

    expect($staff->can('create', Invitation::class))->toBeFalse();

    Mail::assertNothingQueued();
});

test('an invitee can accept a valid invitation', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    $role = Role::query()->where('slug', RoleName::Treasurer->value)->firstOrFail();
    setActiveOrganization($organization->id);

    ['invitation' => $invitation, 'plainTextToken' => $token] = app(InviteMember::class)->handle(
        $organization,
        'invitee@example.com',
        $role,
    );

    $invitee = User::factory()->create(['email' => 'invitee@example.com']);

    $this->actingAs($invitee)
        ->get(route('invitations.accept', ['invitation' => $invitation->id, 'token' => $token]))
        ->assertRedirect(route('dashboard'));

    $membership = OrganizationMembership::query()
        ->where('user_id', $invitee->id)
        ->where('organization_id', $organization->id)
        ->first();

    expect($membership)->not->toBeNull()
        ->and($membership?->role_id)->toBe($role->id)
        ->and(session(EnsureActiveOrganization::SESSION_KEY))->toBe($organization->id)
        ->and($invitation->fresh()->isUsed())->toBeTrue();
});

test('an expired invitation cannot be accepted', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    $role = Role::query()->where('slug', RoleName::Member->value)->firstOrFail();
    setActiveOrganization($organization->id);

    ['invitation' => $invitation, 'plainTextToken' => $token] = app(InviteMember::class)->handle(
        $organization,
        'expired@example.com',
        $role,
    );

    $this->travel(8)->days();

    $invitee = User::factory()->create(['email' => 'expired@example.com']);

    $this->actingAs($invitee)
        ->get(route('invitations.accept', ['invitation' => $invitation->id, 'token' => $token]))
        ->assertForbidden();

    expect(OrganizationMembership::query()->where('user_id', $invitee->id)->exists())->toBeFalse()
        ->and($invitation->fresh()->used_at)->toBeNull();
});

test('a used invitation cannot be accepted again', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    $role = Role::query()->where('slug', RoleName::Member->value)->firstOrFail();
    setActiveOrganization($organization->id);

    ['invitation' => $invitation, 'plainTextToken' => $token] = app(InviteMember::class)->handle(
        $organization,
        'reuse@example.com',
        $role,
    );

    $invitee = User::factory()->create(['email' => 'reuse@example.com']);

    $this->actingAs($invitee)
        ->get(route('invitations.accept', ['invitation' => $invitation->id, 'token' => $token]))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($invitee)
        ->get(route('invitations.accept', ['invitation' => $invitation->id, 'token' => $token]))
        ->assertForbidden();
});

test('a user with a different email cannot accept the invitation', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    $role = Role::query()->where('slug', RoleName::Member->value)->firstOrFail();
    setActiveOrganization($organization->id);

    ['invitation' => $invitation, 'plainTextToken' => $token] = app(InviteMember::class)->handle(
        $organization,
        'intended@example.com',
        $role,
    );

    $other = User::factory()->create(['email' => 'other@example.com']);

    $this->actingAs($other)
        ->get(route('invitations.accept', ['invitation' => $invitation->id, 'token' => $token]))
        ->assertForbidden();

    expect(OrganizationMembership::query()->where('user_id', $other->id)->exists())->toBeFalse()
        ->and($invitation->fresh()->isUsed())->toBeFalse();
});

test('guests are redirected to login when opening an invitation link', function () {
    $organization = Organization::factory()->create();
    $role = Role::query()->where('slug', RoleName::Member->value)->firstOrFail();
    setActiveOrganization($organization->id);

    ['invitation' => $invitation, 'plainTextToken' => $token] = app(InviteMember::class)->handle(
        $organization,
        'guest@example.com',
        $role,
    );

    $this->get(route('invitations.accept', ['invitation' => $invitation->id, 'token' => $token]))
        ->assertRedirect(route('login'));
});
