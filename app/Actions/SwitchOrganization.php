<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SwitchOrganization
{
    public function handle(User $user, string $organizationId): void
    {
        if (! $user->memberships()->where('organization_id', $organizationId)->exists()) {
            throw new HttpException(403, __('You are not allowed to perform this action.'));
        }

        EnsureActiveOrganization::set($organizationId);
    }
}
