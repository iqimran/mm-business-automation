<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (UserRole::cases() as $roleEnum) {
            // create role
            $role = Role::firstOrCreate(['name' => $roleEnum->value]);

            // create & assign permissions
            foreach ($roleEnum->permissions() as $perm) {
                $permission = Permission::firstOrCreate(['name' => $perm]);
                $role->givePermissionTo($permission);
            }
        }
    }
}
