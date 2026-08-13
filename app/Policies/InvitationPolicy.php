<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::UsersManage);
    }

    public function view(User $user, Invitation $invitation): bool
    {
        return $user->hasPermission(PermissionName::UsersManage);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::UsersManage);
    }

    public function update(User $user, Invitation $invitation): bool
    {
        return $user->hasPermission(PermissionName::UsersManage);
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        return $user->hasPermission(PermissionName::UsersManage);
    }
}
