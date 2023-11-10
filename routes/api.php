<?php

use App\Helpers\V1\ResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\ProfilePhotoController;
use App\Http\Controllers\V1\RegistrationController;
use App\Http\Controllers\V1\SupplierController;
use App\Http\Controllers\V1\SupplierTrashController;
use App\Http\Controllers\V1\UserController;
use App\Http\Controllers\V1\UserInviteController;
use App\Http\Controllers\V1\UserRoleController;
use App\Http\Resources\UserResource;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|c
*/

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return new UserResource($request->user());
    });

    Route::prefix('profile')->as('profile.')->group(function () {
        Route::controller(ProfilePhotoController::class)->group(function () {
            Route::post('/photo', 'store')->name('store');
            Route::delete('/photo', 'destroy')->name('destroy');
        });
        Route::controller(ProfileController::class)->group(function () {
            Route::put('/', 'update')->name('update');
            Route::delete('/', 'destroy')->name('destroy');
        });
    });

    Route::prefix('users')->as('users.')->group(function () {
        Route::controller(UserController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::delete('/', 'destroy')->name('destroy');
            Route::put('/{user}/role', 'updateRole')->name('update.role');
        });

        Route::post('/invite', [UserInviteController::class, 'store'])->name('invite.store');
    });

    Route::prefix('suppliers')->as('suppliers.')->group(function () {
        Route::controller(SupplierTrashController::class)->group(function () {
            Route::get('/trash', 'index')->name('trash.index');
            Route::put('/trash', 'restore')->name('trash.restore');
            Route::delete('/trash/empty', 'empty')->name('trash.empty');
            Route::delete('/trash', 'destroy')->name('trash.destroy');
        });

        Route::controller(SupplierController::class)->group(function () {
            Route::post('/', 'store')->name('store');
            Route::get('/export', 'export')->name('export');
            Route::get('/{supplier}', 'show')->name('show');
            Route::get('/', 'index')->name('index');
            Route::put('/{supplier}', 'update')->name('update');
            Route::delete('/', 'destroy')->name('destroy');
        });
    });

    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
    Route::get('/user-roles', [UserRoleController::class, 'index'])->name('user-role.index');
});


Route::middleware(['guest'])->group(function () {
    Route::get('/register', [RegistrationController::class, 'check'])->name('registration.check');
    Route::get('/users/invite/{token}', [UserInviteController::class, 'show'])->name('user.invite.show');
});

