<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('license-api', function (Request $request): array {
            $licenseCode = (string) $request->header('X-License-Code', 'anonymous');
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(60)->by($licenseCode.'|'.$ip),
                Limit::perMinute(180)->by('ip:'.$ip),
            ];
        });
    }
}
