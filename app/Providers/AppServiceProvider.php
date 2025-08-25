<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        RateLimiter::for('client', function (Request $request) {
            $client = (string) $request->header('X-Client-Id', 'guest');
            $max = (int) config('integrations.rate_limit_per_minute', 60);
            return Limit::perMinute($max)->by($client);
        });
    }
}
