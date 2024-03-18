<?php

namespace Database\Seeders;

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

            "access customers",
            "create customers",
            "update customers",
            "delete customers",
            "force delete customers",
            "restore customers",

            "access suppliers",
            "create suppliers",
            "update suppliers",
            "delete suppliers",
            "force delete suppliers",
            "restore suppliers",

            "access categories",
            "create categories",
            "update categories",
            "delete categories",
            "force delete categories",
            "restore categories",

            "access units",
            "create units",
            "update units",
            "delete units",
            "force delete units",
            "restore units",

            "access products",
            "create products",
            "update products",
            "delete products",
            "force delete products",
            "restore products",

            "access purchases",
            "create purchases",
            "update purchases",
            "approve purchases",
            "delete purchases",

            "access invoices",
            "create invoices",
            "update invoices",
            "approve invoices",
            "delete invoices",
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
            "access customers",
            "create customers",
            "update customers",
            "delete customers",

            "access suppliers",
            "create suppliers",
            "update suppliers",
            "delete suppliers",

            "access categories",
            "create categories",
            "update categories",
            "delete categories",

            "access units",
            "create units",
            "update units",
            "delete units",

            "access products",
            "create products",
            "update products",
            "delete products",

            "access purchases",
            "create purchases",
            "update purchases",
            "delete purchases",

            "access invoices",
            "create invoices",
            "update invoices",
            "delete invoices",
        ];

        foreach ($staffPermissions as $permission) {
            $staffRole->givePermissionTo($permission);
        }
    }
}
