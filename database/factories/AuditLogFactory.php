<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'action' => 'auth.login',
            'subject_type' => User::class,
            'subject_id' => fn (array $attributes): string => (string) $attributes['actor_id'],
            'metadata' => ['email' => fake()->safeEmail()],
            'created_at' => now(),
        ];
    }
}
