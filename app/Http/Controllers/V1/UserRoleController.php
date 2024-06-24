<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        try {
            $data = Role::all()->pluck('name')->toArray();
            return ResponseFormatter::success(data: $data);
        } catch (\Exception $e) {
            return ResponseFormatter::error(errors: [$e->getMessage()]);
        }
    }

}
