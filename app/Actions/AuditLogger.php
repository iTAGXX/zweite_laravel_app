<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @var list<string>
     */
    private const array ALLOWED_METADATA_KEYS = [
        'email',
        'role_id',
        'role_slug',
        'user_id',
        'ip',
        'name',
        'slug',
        'from_role_id',
        'to_role_id',
    ];

    /**
     * @var list<string>
     */
    private const array SENSITIVE_KEY_FRAGMENTS = [
        'password',
        'token',
        'secret',
        'iban',
        'recovery',
        'two_factor',
    ];

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        string $action,
        ?User $actor = null,
        ?string $organizationId = null,
        ?Model $subject = null,
        array $metadata = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'organization_id' => $organizationId ?? $this->resolveOrganizationId($subject, $actor),
            'action' => $action,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject !== null ? (string) $subject->getKey() : null,
            'metadata' => $this->sanitizeMetadata($metadata),
            'created_at' => now(),
        ]);
    }

    private function resolveOrganizationId(?Model $subject, ?User $actor): ?string
    {
        $fromSubject = $subject?->getAttribute('organization_id');

        if (is_string($fromSubject) && $fromSubject !== '') {
            return $fromSubject;
        }

        $active = EnsureActiveOrganization::id();

        if ($active !== null) {
            return $active;
        }

        if ($actor === null) {
            return null;
        }

        $fromMembership = $actor->memberships()->value('organization_id');

        return is_string($fromMembership) ? $fromMembership : null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $clean = [];

        foreach ($metadata as $key => $value) {
            if (! in_array($key, self::ALLOWED_METADATA_KEYS, true) || $this->isSensitiveKey($key)) {
                continue;
            }

            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
