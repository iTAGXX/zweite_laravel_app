<?php

declare(strict_types=1);

use App\Actions\AuditLogger;
use App\Actions\InviteMember;
use App\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\Concerns\BelongsToOrganization;
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

test('audit logs use the belongs to organization scope', function () {
    expect(class_uses_recursive(AuditLog::class))->toContain(BelongsToOrganization::class);
});

test('creating an invitation writes an audit log without the token', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    $role = Role::query()->where('slug', RoleName::Staff->value)->firstOrFail();

    $this->actingAs($admin);
    setActiveOrganization($organization->id);

    ['invitation' => $invitation, 'plainTextToken' => $token] = app(InviteMember::class)->handle(
        $organization,
        'invitee@example.com',
        $role,
    );

    $log = AuditLog::query()->where('action', 'invitation.created')->first();

    expect($log)->not->toBeNull()
        ->and($log?->actor_id)->toBe($admin->id)
        ->and($log?->subject_id)->toBe($invitation->id)
        ->and($log?->metadata)->toHaveKey('email')
        ->and($log?->metadata)->not->toHaveKey('token')
        ->and(json_encode($log?->metadata))->not->toContain($token);
});

test('membership role changes are audited', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);
    $this->actingAs($admin);

    $member = User::factory()->create();
    $membership = OrganizationMembership::factory()
        ->for($organization)
        ->for($member)
        ->forRole(RoleName::Member)
        ->create();

    $fromRoleId = $membership->role_id;
    $staffRole = Role::query()->where('slug', RoleName::Staff->value)->firstOrFail();

    $membership->update(['role_id' => $staffRole->id]);

    $log = AuditLog::query()->where('action', 'membership.role_changed')->first();

    expect($log)->not->toBeNull()
        ->and($log?->actor_id)->toBe($admin->id)
        ->and($log?->metadata['user_id'])->toBe($member->id)
        ->and($log?->metadata['from_role_id'])->toBe($fromRoleId)
        ->and($log?->metadata['to_role_id'])->toBe($staffRole->id);
});

test('successful login is written to the audit log', function () {
    $user = User::factory()->withOrganization(RoleName::Member)->create();
    $organization = $user->organizations()->firstOrFail();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    setActiveOrganization($organization->id);

    $log = AuditLog::query()
        ->where('action', 'auth.login')
        ->where('actor_id', $user->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log?->organization_id)->toBe($organization->id)
        ->and($log?->metadata['email'])->toBe($user->email)
        ->and($log?->metadata)->not->toHaveKey('password');
});

test('audit metadata drops secrets passwords tokens and ibans', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $log = app(AuditLogger::class)->handle(
        action: 'test.redact',
        actor: $admin,
        organizationId: $organization->id,
        metadata: [
            'email' => 'a@example.com',
            'password' => 'secret-password',
            'token' => 'plain-token',
            'full_iban' => 'DE89370400440532013000',
            'two_factor_secret' => 'otp-secret',
            'role_id' => 1,
        ],
    );

    expect($log->metadata)->toBe([
        'email' => 'a@example.com',
        'role_id' => 1,
    ]);
});

test('staff cannot view or mutate audit logs', function () {
    $staff = User::factory()->withOrganization(RoleName::Staff)->create();
    $organization = $staff->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $log = AuditLog::factory()->for($organization)->create(['action' => 'auth.login']);

    $this->actingAs($staff)
        ->get(route('audit'))
        ->assertForbidden();

    expect($staff->can('viewAny', AuditLog::class))->toBeFalse()
        ->and($staff->can('view', $log))->toBeFalse()
        ->and($staff->can('update', $log))->toBeFalse()
        ->and($staff->can('delete', $log))->toBeFalse()
        ->and($log->update(['action' => 'hacked']))->toBeFalse()
        ->and($log->fresh()?->action)->toBe('auth.login')
        ->and($log->delete())->toBeFalse();

    expect(AuditLog::query()->whereKey($log->id)->exists())->toBeTrue();
});

test('admins can view the audit log but cannot mutate entries', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    AuditLog::factory()->for($organization)->create(['action' => 'auth.login']);

    $this->actingAs($admin)
        ->get(route('audit'))
        ->assertOk()
        ->assertSee('auth.login');

    Livewire::test('audit-log')
        ->assertOk()
        ->assertSee('auth.login');

    $log = AuditLog::query()->where('action', 'auth.login')->firstOrFail();

    expect($admin->can('viewAny', AuditLog::class))->toBeTrue()
        ->and($admin->can('update', $log))->toBeFalse()
        ->and($admin->can('delete', $log))->toBeFalse();
});

test('audit logs are isolated per organization', function () {
    $adminA = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationA = $adminA->organizations()->firstOrFail();
    $logA = AuditLog::factory()->for($organizationA)->create(['action' => 'org-a.event']);

    $adminB = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationB = $adminB->organizations()->firstOrFail();
    $logB = AuditLog::factory()->for($organizationB)->create(['action' => 'org-b.event']);

    $this->actingAs($adminA);
    setActiveOrganization($organizationA->id);

    expect(AuditLog::query()->find($logA->id))->not->toBeNull()
        ->and(AuditLog::query()->find($logB->id))->toBeNull()
        ->and(AuditLog::query()->pluck('action')->all())->toContain('org-a.event')
        ->and(AuditLog::query()->pluck('action')->all())->not->toContain('org-b.event');
});
