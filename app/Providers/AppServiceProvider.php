<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // บังคับ HTTPS เมื่ออยู่บน production (server จริง)
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    }
}