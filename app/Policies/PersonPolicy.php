<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Person;
use App\Models\User;

class PersonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::PeopleManage);
    }

    public function view(User $user, Person $person): bool
    {
        return $user->hasPermission(PermissionName::PeopleManage);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::PeopleManage);
    }

    public function update(User $user, Person $person): bool
    {
        return $user->hasPermission(PermissionName::PeopleManage);
    }

    public function delete(User $user, Person $person): bool
    {
        return false;
    }

    public function restore(User $user, Person $person): bool
    {
        return false;
    }

    public function forceDelete(User $user, Person $person): bool
    {
        return false;
    }

    public function archive(User $user, Person $person): bool
    {
        return $user->hasPermission(PermissionName::PeopleManage);
    }

    public function unarchive(User $user, Person $person): bool
    {
        return $user->hasPermission(PermissionName::PeopleManage);
    }
}
