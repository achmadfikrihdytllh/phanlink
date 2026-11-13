<?php

namespace App\Providers;

use App\Models\AccessToken;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

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
        Sanctum::usePersonalAccessTokenModel(AccessToken::class);

        // Reset emails link to the Angular app, not to a Laravel page.
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $base = rtrim(explode(',', env('FRONTEND_URL', 'http://localhost:4200'))[0], '/');

            return $base . '/reset-password?token=' . $token . '&email=' . urlencode($user->getEmailForPasswordReset());
        });
    }
}
