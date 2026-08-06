<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request): Limit {
            $key = Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip();

            return Limit::perMinute(5)->by(hash('sha256', $key));
        });

        RateLimiter::for('registration', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('password-email', function (Request $request): Limit {
            $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(3)->by(hash('sha256', $key));
        });
        RateLimiter::for('oauth', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('tmdb', fn (Request $request): Limit => Limit::perMinute(30)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('favorites', fn (Request $request): Limit => Limit::perMinute(60)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }
}
