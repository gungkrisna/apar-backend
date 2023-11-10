<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegistrationController extends Controller
{
    public function check(): JsonResponse
    {
        if (User::all()->count() === 0) {
            return ResponseFormatter::success();
        } else {
            return ResponseFormatter::error(403, 'Forbidden');
        }
    }
}
