<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * @mixin Model
 *
 * @phpstan-require-extends Model
 *
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 */
trait HasExpirableToken
{
    public function isExpired(): bool
    {
        return ! $this->expires_at->isFuture();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed();
    }

    public function consume(string $plainTextToken): bool
    {
        $storedHash = $this->getRawOriginal('token');

        if (! is_string($storedHash) || ! $this->isValid() || ! Hash::check($plainTextToken, $storedHash)) {
            return false;
        }

        $this->forceFill([
            'used_at' => now(),
        ])->save();

        return true;
    }
}
