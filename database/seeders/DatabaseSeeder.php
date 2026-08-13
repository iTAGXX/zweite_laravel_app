<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
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
        $this->call(RoleSeeder::class);

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

        $adminRoleId = Role::query()->where('slug', RoleName::Admin->value)->firstOrFail()->id;

        OrganizationMembership::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            [
                'role_id' => $adminRoleId,
            ],
        );
    }
}
