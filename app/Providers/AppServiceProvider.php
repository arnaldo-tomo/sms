<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // O papel "admin" tem acesso total (super-admin bypass).
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        // Rate limit da API pública: por empresa (messages_per_minute).
        RateLimiter::for('sms-api', function (Request $request) {
            $company = $request->attributes->get('company');
            $perMinute = $company instanceof Company ? max(1, $company->messages_per_minute) : 60;
            $key = $company instanceof Company ? 'company:'.$company->id : 'ip:'.$request->ip();

            return Limit::perMinute($perMinute)->by($key);
        });
    }
}
