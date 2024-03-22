<?php

use App\Helpers\V1\ResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\V1\PurchaseImageController;
use App\Http\Controllers\V1\CategoryController;
use App\Http\Controllers\V1\CategoryImageController;
use App\Http\Controllers\V1\CategoryTrashController;
use App\Http\Controllers\V1\CustomerController;
use App\Http\Controllers\V1\CustomerTrashController;
use App\Http\Controllers\V1\FeatureController;
use App\Http\Controllers\V1\InvoiceController;
use App\Http\Controllers\V1\InvoiceImageController;
use App\Http\Controllers\V1\ProductController;
use App\Http\Controllers\V1\ProductImageController;
use App\Http\Controllers\V1\ProductTrashController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\ProfilePhotoController;
use App\Http\Controllers\V1\PurchaseController;
use App\Http\Controllers\V1\RegistrationController;
use App\Http\Controllers\V1\SupplierController;
use App\Http\Controllers\V1\SupplierTrashController;
use App\Http\Controllers\V1\UnitController;
use App\Http\Controllers\V1\UnitTrashController;
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

    Route::prefix('customers')->as('customers.')->group(function () {
        Route::controller(CustomerTrashController::class)->group(function () {
            Route::get('/trash', 'index')->name('trash.index');
            Route::put('/trash', 'restore')->name('trash.restore');
            Route::delete('/trash/empty', 'empty')->name('trash.empty');
            Route::delete('/trash', 'destroy')->name('trash.destroy');
        });

        Route::controller(CustomerController::class)->group(function () {
            Route::post('/', 'store')->name('store');
            Route::get('/export', 'export')->name('export');
            Route::get('/{customer}', 'show')->name('show');
            Route::get('/', 'index')->name('index');
            Route::put('/{customer}', 'update')->name('update');
            Route::delete('/', 'destroy')->name('destroy');
        });
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

    Route::prefix('categories')->as('categories.')->group(function () {
        Route::controller(CategoryImageController::class)->group(function () {
            Route::post('/image', 'store')->name('store');
            Route::delete('/image/{id}', 'destroy')->name('destroy');
        });    
        Route::controller(CategoryTrashController::class)->group(function () {
            Route::get('/trash', 'index')->name('trash.index');
            Route::put('/trash', 'restore')->name('trash.restore');
            Route::delete('/trash/empty', 'empty')->name('trash.empty');
            Route::delete('/trash', 'destroy')->name('trash.destroy');
        });

        Route::controller(CategoryController::class)->group(function () {
            Route::post('/', 'store')->name('store');
            Route::get('/export', 'export')->name('export');
            Route::get('/{category}', 'show')->name('show');
            Route::get('/', 'index')->name('index');
            Route::put('/{category}', 'update')->name('update');
            Route::delete('/', 'destroy')->name('destroy');
        });
    });

    Route::prefix('units')->as('units.')->group(function () {
        Route::controller(UnitTrashController::class)->group(function () {
            Route::get('/trash', 'index')->name('trash.index');
            Route::put('/trash', 'restore')->name('trash.restore');
            Route::delete('/trash/empty', 'empty')->name('trash.empty');
            Route::delete('/trash', 'destroy')->name('trash.destroy');
        });

        Route::controller(UnitController::class)->group(function () {
            Route::post('/', 'store')->name('store');
            Route::get('/export', 'export')->name('export');
            Route::get('/{unit}', 'show')->name('show');
            Route::get('/', 'index')->name('index');
            Route::put('/{unit}', 'update')->name('update');
            Route::delete('/', 'destroy')->name('destroy');
        });
    });

    Route::prefix('products')->as('products.')->group(function () {
        Route::controller(ProductImageController::class)->group(function () {
            Route::post('/image', 'store')->name('store');
            Route::delete('/image/{id}', 'destroy')->name('destroy');
        });    
        Route::controller(ProductTrashController::class)->group(function () {
            Route::get('/trash', 'index')->name('trash.index');
            Route::put('/trash', 'restore')->name('trash.restore');
            Route::delete('/trash/empty', 'empty')->name('trash.empty');
            Route::delete('/trash', 'destroy')->name('trash.destroy');
        });

        Route::controller(ProductController::class)->group(function () {
            Route::put('/update-status', 'updateStatus')->name('updateStatus');
            Route::post('/serial-number/generate', 'generateSerialNumber')->name('generateSerialNumber');
            Route::get('/serial-number/{serialNumber}', 'getBySerialNumber')->name('getBySerialNumber');
            Route::post('/', 'store')->name('store');
            Route::get('/export', 'export')->name('export');
            Route::get('/{product}', 'show')->name('show');
            Route::get('/', 'index')->name('index');
            Route::put('/{product}', 'update')->name('update');
            Route::delete('/', 'destroy')->name('destroy');
        });
    });

    Route::prefix('purchases')->as('purchases.')->group(function () {
        Route::controller(PurchaseImageController::class)->group(function () {
            Route::post('/image', 'store')->name('store');
            Route::delete('/image/{id}', 'destroy')->name('destroy');
        });    

        Route::controller(PurchaseController::class)->group(function () {
            Route::post('/po-number/generate', 'generatePurchaseNumber')->name('generatePurchaseNumber');
            Route::post('/{purchase}/approve', 'approve')->name('approve');
            Route::post('/', 'store')->name('store');
            Route::get('/export', 'export')->name('export');
            Route::get('/{purchase}', 'show')->name('show');
            Route::get('/', 'index')->name('index');
            Route::put('/{purchase}', 'update')->name('update');
            Route::delete('/', 'destroy')->name('destroy');
        });
    });

    Route::prefix('invoices')->as('invoices.')->group(function () {
        Route::controller(InvoiceImageController::class)->group(function () {
            Route::post('/image', 'store')->name('store');
            Route::delete('/image/{id}', 'destroy')->name('destroy');
        });    

        Route::controller(InvoiceController::class)->group(function () {
            Route::post('/invoice-number/generate', 'generateInvoiceNumber')->name('generateInvoiceNumber');
            Route::post('/{invoice}/approve', 'approve')->name('approve');
            Route::post('/', 'store')->name('store');
            Route::get('/export', 'export')->name('export');
            Route::get('/{invoice}', 'show')->name('show');
            Route::get('/', 'index')->name('index');
            Route::put('/{invoice}', 'update')->name('update');
            Route::delete('/', 'destroy')->name('destroy');
        });
    });

    Route::apiResource('/features', FeatureController::class);

    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
    Route::get('/user-roles', [UserRoleController::class, 'index'])->name('user-role.index');
});


Route::middleware(['guest'])->group(function () {
    Route::get('/register', [RegistrationController::class, 'check'])->name('registration.check');
    Route::get('/users/invite/{token}', [UserInviteController::class, 'show'])->name('user.invite.show');
});
