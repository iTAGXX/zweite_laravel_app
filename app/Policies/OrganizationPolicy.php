<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->canManageActiveOrganization($user, $organization);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->canManageActiveOrganization($user, $organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return false;
    }

    public function restore(User $user, Organization $organization): bool
    {
        return false;
    }

    public function forceDelete(User $user, Organization $organization): bool
    {
        return false;
    }

    private function canManageActiveOrganization(User $user, Organization $organization): bool
    {
        $activeId = EnsureActiveOrganization::id();

        if ($activeId === null || $activeId !== $organization->id) {
            return false;
        }

        return $user->hasPermission(PermissionName::UsersManage);
    }
}
