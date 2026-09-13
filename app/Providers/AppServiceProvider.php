<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Applies wherever a password is validated via Password::defaults()
        // (registration, admin password resets) — beyond just a minimum
        // length, so a "requirement" actually means something.
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers()->symbols());
    }
}
