<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            "access permissions",
            "create permissions",
            "update permissions",
            "delete permissions",

            "access roles",
            "create roles",
            "update roles",
            "delete roles",

            "access users",
            "create users",
            "update users",
            "delete users",

            "access invitations",
            "create invitations",
            "update invitations",
            "delete invitations",

            "access suppliers",
            "create suppliers",
            "update suppliers",
            "delete suppliers",
            "force delete suppliers",
            "restore suppliers",

            "access products",
            "create products",
            "update products",
            "delete products",
            
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                "name" => $permission
            ]);
        }

        Role::create(["name" => "Super Admin"])->givePermissionTo(Permission::all());

        // Staff Permission
        $staffRole = Role::create(["name" => "Staff"]);

        $staffPermissions = [
            "access suppliers",
            "create suppliers",
            "update suppliers",
            "delete suppliers",
        ];

        foreach ($staffPermissions as $permission) {
            $staffRole->givePermissionTo($permission);
        }

        // Customer Permission
        $customerRole = Role::create(["name" => "Customer"]);

        $customerPermissions = [
            "access products",
        ];
        
        foreach ($customerPermissions as $permission) {
            $customerRole->givePermissionTo($permission);
        }
    }
}
