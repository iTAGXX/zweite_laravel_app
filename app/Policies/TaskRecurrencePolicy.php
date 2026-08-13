<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\TaskRecurrence;
use App\Models\User;

class TaskRecurrencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function view(User $user, TaskRecurrence $taskRecurrence): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function update(User $user, TaskRecurrence $taskRecurrence): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function pause(User $user, TaskRecurrence $taskRecurrence): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function resume(User $user, TaskRecurrence $taskRecurrence): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function end(User $user, TaskRecurrence $taskRecurrence): bool
    {
        return $user->hasPermission(PermissionName::TasksManage);
    }

    public function delete(User $user, TaskRecurrence $taskRecurrence): bool
    {
        return false;
    }

    public function restore(User $user, TaskRecurrence $taskRecurrence): bool
    {
        return false;
    }

    public function forceDelete(User $user, TaskRecurrence $taskRecurrence): bool
    {
        return false;
    }
}
