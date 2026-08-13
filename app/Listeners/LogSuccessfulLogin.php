<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\AuditLogger;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $organizationId = EnsureActiveOrganization::id()
            ?? $event->user->memberships()->value('organization_id');

        $this->auditLogger->handle(
            action: 'auth.login',
            actor: $event->user,
            organizationId: is_string($organizationId) ? $organizationId : null,
            subject: $event->user,
            metadata: [
                'email' => $event->user->email,
                'ip' => request()->ip(),
            ],
        );
    }
}
