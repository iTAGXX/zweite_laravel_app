<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function complete(User $user, Task $task): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function assign(User $user, Task $task): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function restore(User $user, Task $task): bool
    {
        return false;
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }
}
