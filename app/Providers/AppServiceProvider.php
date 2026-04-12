<?php

namespace App\Providers;

use App\Services\Security\PromptInjectionGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PromptInjectionGuard::class, static fn (): PromptInjectionGuard => PromptInjectionGuard::fromConfig());

        if (class_exists(TelescopeServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('channel-webhook', function (Request $request) {
            $key = (string) $request->route('webhook_key', '');

            return Limit::perMinute(180)->by($request->ip().':'.$key);
        });

        RateLimiter::for('api-chat', function (Request $request) {
            $userId = $request->user()?->id;

            return Limit::perMinute(60)->by($userId ? 'user:'.$userId : 'ip:'.$request->ip());
        });
    }
}
