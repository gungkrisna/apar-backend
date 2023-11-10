<?php

namespace App\Observers;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionObserver
{
    public function created(Permission $permission)
    {
        $this->syncSuperAdminPermissions();
    }

    public function updated(Permission $permission)
    {
        $this->syncSuperAdminPermissions();
    }

    protected function syncSuperAdminPermissions()
    {
        Role::findByName('Super Admin')->syncPermissions(Permission::all());
    }
}
