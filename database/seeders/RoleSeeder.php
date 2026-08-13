<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * @var array<string, list<PermissionName>>
     */
    private const array MATRIX = [
        RoleName::Admin->value => [PermissionName::UsersManage, PermissionName::FinanceView, PermissionName::AuditView, PermissionName::PeopleManage],
        RoleName::Treasurer->value => [PermissionName::FinanceView],
        RoleName::Member->value => [],
        RoleName::Staff->value => [],
    ];

    public function run(): void
    {
        $permissions = [];

        foreach (PermissionName::cases() as $permission) {
            $permissions[$permission->value] = Permission::query()->firstOrCreate(
                ['slug' => $permission->value],
                ['name' => $permission->name],
            );
        }

        foreach (RoleName::cases() as $roleName) {
            $role = Role::query()->firstOrCreate(
                ['slug' => $roleName->value],
                ['name' => $roleName->name],
            );

            $role->permissions()->sync(
                array_map(
                    fn (PermissionName $permission): int => $permissions[$permission->value]->id,
                    self::MATRIX[$roleName->value],
                ),
            );
        }
    }
}
