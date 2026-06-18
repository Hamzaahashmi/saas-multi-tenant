<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the central DB with default permissions.
     * Each tenant DB gets its own roles/users via TenantRegistrationService.
     */
    public function run(): void
    {
        // Permissions used across the platform
        $permissions = [
            'manage-team',
            'view-billing',
            'manage-billing',
            'view-dashboard',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Default roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $member = Role::firstOrCreate(['name' => 'member']);

        $admin->givePermissionTo($permissions);
        $manager->givePermissionTo(['manage-team', 'view-billing', 'view-dashboard']);
        $member->givePermissionTo(['view-dashboard']);

        $this->command->info('Default roles and permissions seeded.');
    }
}
