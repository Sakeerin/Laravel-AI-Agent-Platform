<?php

namespace App\Providers;

use App\Services\AI\AgentOrchestrator;
use App\Services\AI\AIManager;
use App\Services\Memory\MemoryPromptBuilder;
use App\Services\Tools\BuiltIn\BrowserTool;
use App\Services\Tools\BuiltIn\CalculatorTool;
use App\Services\Tools\BuiltIn\DateTimeTool;
use App\Services\Tools\BuiltIn\FileSystemTool;
use App\Services\Tools\BuiltIn\ShellCommandTool;
use App\Services\Tools\BuiltIn\WebSearchTool;
use App\Services\Tools\ToolExecutor;
use App\Services\Tools\ToolRegistry;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIManager::class);

        $this->app->singleton(ToolRegistry::class, function () {
            $registry = new ToolRegistry;

            $registry->register(new WebSearchTool);
            $registry->register(new BrowserTool);
            $registry->register(new FileSystemTool);
            $registry->register(new ShellCommandTool);
            $registry->register(new CalculatorTool);
            $registry->register(new DateTimeTool);

            return $registry;
        });

        $this->app->singleton(ToolExecutor::class, function ($app) {
            return new ToolExecutor($app->make(ToolRegistry::class));
        });

        $this->app->singleton(AgentOrchestrator::class, function ($app) {
            return new AgentOrchestrator(
                $app->make(AIManager::class),
                $app->make(ToolRegistry::class),
                $app->make(ToolExecutor::class),
                $app->make(MemoryPromptBuilder::class),
            );
        });
    }
}
