<?php

namespace App\Services\AI;

use InvalidArgumentException;

class AIManager
{
    /** @var array<string, AIProvider> */
    private array $providers = [];

    /** @var array<string, string> model alias => provider name */
    private array $modelProviderMap = [
        'claude-sonnet' => 'anthropic',
        'claude-haiku' => 'anthropic',
        'claude-opus' => 'anthropic',
        'gpt-4o' => 'openai',
        'gpt-4o-mini' => 'openai',
        'gpt-4-turbo' => 'openai',
    ];

    public function __construct()
    {
        $this->registerProvider(new AnthropicProvider());
        $this->registerProvider(new OpenAIProvider());
        $this->registerProvider(new OllamaProvider());
    }

    public function registerProvider(AIProvider $provider): void
    {
        $this->providers[$provider->name()] = $provider;
    }

    public function provider(string $name): AIProvider
    {
        if (!isset($this->providers[$name])) {
            throw new InvalidArgumentException("AI provider [{$name}] not registered.");
        }
        return $this->providers[$name];
    }

    public function resolveProvider(string $model): AIProvider
    {
        $providerName = $this->modelProviderMap[$model] ?? null;

        if ($providerName && isset($this->providers[$providerName])) {
            return $this->providers[$providerName];
        }

        return $this->providers['ollama'];
    }

    public function chat(array $messages, string $model, array $options = []): array
    {
        return $this->resolveProvider($model)->chat($messages, $model, $options);
    }

    public function stream(array $messages, string $model, array $options = []): \Generator
    {
        return $this->resolveProvider($model)->stream($messages, $model, $options);
    }

    public function availableModels(): array
    {
        $models = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->models() as $alias => $realModel) {
                $models[$alias] = [
                    'provider' => $provider->name(),
                    'model' => $realModel,
                ];
            }
        }
        return $models;
    }
}
