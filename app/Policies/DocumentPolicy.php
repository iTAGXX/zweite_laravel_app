<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::DocumentsManage);
    }

    public function view(User $user, Document $document): bool
    {
        return $user->hasPermission(PermissionName::DocumentsManage);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::DocumentsManage);
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasPermission(PermissionName::DocumentsManage);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermission(PermissionName::DocumentsManage);
    }

    public function restore(User $user, Document $document): bool
    {
        return false;
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }
}
