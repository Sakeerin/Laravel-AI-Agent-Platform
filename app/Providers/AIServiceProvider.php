<?php

namespace App\Providers;

use App\Services\AI\AIManager;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIManager::class);
    }
}
