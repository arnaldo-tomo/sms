<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'contacts.view',
            'contacts.manage',
            'sms.send',
            'messages.view',
            'devices.view',
            'devices.manage',
            'settings.manage',
            'users.manage',
            'audit.view',
            'companies.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'contacts.view', 'contacts.manage', 'sms.send',
            'messages.view', 'devices.view', 'devices.manage',
        ]);

        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->syncPermissions([
            'contacts.view', 'sms.send', 'messages.view', 'devices.view',
        ]);
    }
}
