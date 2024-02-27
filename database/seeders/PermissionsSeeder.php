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

            "access categories",
            "create categories",
            "update categories",
            "delete categories",
            "force delete categories",
            "restore categories",
        ];

        foreach ($permissions as $permission) {
            if (!Permission::where("name", $permission)->first()) {
                Permission::create([
                    "name" => $permission
                ]);
            }
        }

        $superAdminRole = Role::where("name", "Super Admin")->first();

        if(!$superAdminRole){
            $superAdminRole = Role::create(["name" => "Super Admin"]);
        }

        $superAdminRole->givePermissionTo(Permission::all());

        // Staff Permission

        $staffRole = Role::where("name", "Staff")->first();

        if(!$staffRole){
            $staffRole = Role::create(["name" => "Staff"]);
        }

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

        $customerRole = Role::where("name", "Customer")->first();

        if(!$customerRole){
            $customerRole = Role::create(["name" => "Customer"]);
        }
        
        $customerPermissions = [
            "access products",
        ];
        
        foreach ($customerPermissions as $permission) {
            $customerRole->givePermissionTo($permission);
        }
    }
}
