<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
        // Reset emails link to the Angular app, not to a Laravel page.
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $base = rtrim(explode(',', env('FRONTEND_URL', 'http://localhost:4200'))[0], '/');

            return $base . '/reset-password?token=' . $token . '&email=' . urlencode($user->getEmailForPasswordReset());
        });
    }
}
