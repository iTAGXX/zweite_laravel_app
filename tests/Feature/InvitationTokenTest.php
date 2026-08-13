<?php

use App\Actions\IssueInvitationToken;
use App\Models\Invitation;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;

test('issued invitation tokens are stored hashed not as plaintext', function () {
    $organization = Organization::factory()->create();

    ['invitation' => $invitation, 'plainTextToken' => $plainTextToken] = (new IssueInvitationToken)->handle(
        $organization,
        'invitee@example.com',
    );

    $storedHash = $invitation->getRawOriginal('token');

    expect($invitation->isValid())->toBeTrue()
        ->and($storedHash)->not->toBe($plainTextToken)
        ->and(Hash::isHashed($storedHash))->toBeTrue()
        ->and(Hash::check($plainTextToken, $storedHash))->toBeTrue();
});

test('a valid invitation token can be consumed only once', function () {
    $organization = Organization::factory()->create();

    ['invitation' => $invitation, 'plainTextToken' => $plainTextToken] = (new IssueInvitationToken)->handle(
        $organization,
        'invitee@example.com',
    );

    expect($invitation->consume($plainTextToken))->toBeTrue()
        ->and($invitation->fresh()->isUsed())->toBeTrue()
        ->and($invitation->fresh()->consume($plainTextToken))->toBeFalse();
});

test('an expired invitation token is rejected', function () {
    $organization = Organization::factory()->create();

    ['invitation' => $invitation, 'plainTextToken' => $plainTextToken] = (new IssueInvitationToken)->handle(
        $organization,
        'invitee@example.com',
    );

    $this->travel(8)->days();

    $invitation->refresh();

    expect($invitation->isExpired())->toBeTrue()
        ->and($invitation->consume($plainTextToken))->toBeFalse()
        ->and($invitation->fresh()->used_at)->toBeNull();
});

test('a used invitation token is rejected', function () {
    $invitation = Invitation::factory()->used()->create();

    expect($invitation->isUsed())->toBeTrue()
        ->and($invitation->isValid())->toBeFalse();
});
