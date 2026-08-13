<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\OrganizationInvitation;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Role;
use Illuminate\Support\Facades\Mail;

class InviteMember
{
    public function __construct(private IssueInvitationToken $issueInvitationToken) {}

    /**
     * @return array{invitation: Invitation, plainTextToken: string}
     */
    public function handle(Organization $organization, string $email, Role $role): array
    {
        $issued = $this->issueInvitationToken->handle($organization, $email, $role);

        Mail::to($email)->send(new OrganizationInvitation(
            organizationName: $organization->name,
            acceptUrl: route('invitations.accept', [
                'invitation' => $issued['invitation']->id,
                'token' => $issued['plainTextToken'],
            ]),
            expiresAt: $issued['invitation']->expires_at,
        ));

        return $issued;
    }
}
