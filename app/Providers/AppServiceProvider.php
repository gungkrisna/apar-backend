<?php

namespace App\Providers;

use Validator;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FacadesValidator::extend('unique_users', function ($attribute, $value, $parameters, $validator) {
            if(User::where('email', $value)->exists())
                return false;
            return true;
        });

        FacadesValidator::extend('unique_invitations', function ($attribute, $value, $parameters, $validator) {
            if(Invitation::where('email', $value)->exists())
                return false;
            return true;
        });
    }
}
