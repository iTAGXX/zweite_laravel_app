<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Invitation;
use App\Models\Organization;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class IssueInvitationToken
{
    public const int DEFAULT_EXPIRY_DAYS = 7;

    /**
     * @return array{invitation: Invitation, plainTextToken: string}
     */
    public function handle(Organization $organization, string $email, ?CarbonInterface $expiresAt = null): array
    {
        $plainTextToken = Str::random(64);

        $invitation = Invitation::query()->create([
            'organization_id' => $organization->id,
            'email' => $email,
            'token' => $plainTextToken,
            'expires_at' => $expiresAt ?? now()->addDays(self::DEFAULT_EXPIRY_DAYS),
        ]);

        return [
            'invitation' => $invitation,
            'plainTextToken' => $plainTextToken,
        ];
    }
}
