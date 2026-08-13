<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Invitation;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('the permission matrix matches the standard roles', function (RoleName $role, PermissionName $permission, bool $allowed) {
    $user = User::factory()->withOrganization($role)->create();
    $organization = $user->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    expect($user->hasPermission($permission))->toBe($allowed)
        ->and($user->can($permission->value))->toBe($allowed);
})->with([
    'admin can manage users' => [RoleName::Admin, PermissionName::UsersManage, true],
    'admin can view finance' => [RoleName::Admin, PermissionName::FinanceView, true],
    'admin can view audit' => [RoleName::Admin, PermissionName::AuditView, true],
    'admin can manage people' => [RoleName::Admin, PermissionName::PeopleManage, true],
    'treasurer cannot manage users' => [RoleName::Treasurer, PermissionName::UsersManage, false],
    'treasurer can view finance' => [RoleName::Treasurer, PermissionName::FinanceView, true],
    'treasurer cannot view audit' => [RoleName::Treasurer, PermissionName::AuditView, false],
    'treasurer cannot manage people' => [RoleName::Treasurer, PermissionName::PeopleManage, false],
    'member cannot manage users' => [RoleName::Member, PermissionName::UsersManage, false],
    'member cannot view finance' => [RoleName::Member, PermissionName::FinanceView, false],
    'member cannot view audit' => [RoleName::Member, PermissionName::AuditView, false],
    'member cannot manage people' => [RoleName::Member, PermissionName::PeopleManage, false],
    'staff cannot manage users' => [RoleName::Staff, PermissionName::UsersManage, false],
    'staff cannot view finance' => [RoleName::Staff, PermissionName::FinanceView, false],
    'staff cannot view audit' => [RoleName::Staff, PermissionName::AuditView, false],
    'staff cannot manage people' => [RoleName::Staff, PermissionName::PeopleManage, false],
]);

test('invitation policies follow users manage', function (RoleName $role, bool $allowed) {
    $user = User::factory()->withOrganization($role)->create();
    $organization = $user->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $invitation = Invitation::factory()->for($organization)->create();

    expect($user->can('create', Invitation::class))->toBe($allowed)
        ->and($user->can('view', $invitation))->toBe($allowed)
        ->and($user->can('update', $invitation))->toBe($allowed)
        ->and($user->can('delete', $invitation))->toBe($allowed);
})->with([
    'admin' => [RoleName::Admin, true],
    'treasurer' => [RoleName::Treasurer, false],
    'staff' => [RoleName::Staff, false],
]);
