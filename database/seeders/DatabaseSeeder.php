<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public const string DEMO_ADMIN_EMAIL = 'test@example.com';

    public const string DEMO_ORGANIZATION_SLUG = 'demo';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => self::DEMO_ADMIN_EMAIL],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $organization = Organization::query()->firstOrCreate(
            ['slug' => self::DEMO_ORGANIZATION_SLUG],
            ['name' => 'Demo-Organisation'],
        );

        OrganizationMembership::query()->firstOrCreate([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);
    }
}
