<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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

        // Every registration emails an OTP, so this caps how hard one address
        // can be flooded and how fast the SMTP quota can be burned. The per-IP
        // limit stays generous since a whole training hall may share one IP.
        RateLimiter::for('registration', fn (Request $request) => [
            Limit::perMinute(5)->by('registration-email:'.(is_string($email = $request->input('email')) ? Str::lower($email) : '')),
            Limit::perMinute(20)->by('registration-ip:'.$request->ip()),
        ]);
    }
}
