<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Invitation;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AcceptInvitation
{
    public function handle(User $user, string $invitationId, string $plainTextToken): Invitation
    {
        $invitation = Invitation::findForAcceptance($invitationId);

        if (! $invitation instanceof Invitation || $invitation->role_id === null || ! $invitation->isValid()) {
            $this->reject();
        }

        if (! hash_equals(Str::lower($user->email), Str::lower($invitation->email))) {
            $this->reject();
        }

        if (! $invitation->consume($plainTextToken)) {
            $this->reject();
        }

        OrganizationMembership::query()->firstOrCreate(
            [
                'organization_id' => $invitation->organization_id,
                'user_id' => $user->id,
            ],
            [
                'role_id' => $invitation->role_id,
            ],
        );

        EnsureActiveOrganization::set($invitation->organization_id);

        return $invitation;
    }

    public function reject(): never
    {
        throw new HttpException(403, __('This invitation is invalid or has expired.'));
    }
}
