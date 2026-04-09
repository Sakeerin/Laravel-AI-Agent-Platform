<?php

namespace App\Services\Memory;

use App\Services\AI\OllamaProvider;
use App\Services\AI\OpenAIProvider;
use App\Support\AgentSettings;

class EmbeddingService
{
    public function __construct(
        private readonly OpenAIProvider $openai,
        private readonly OllamaProvider $ollama,
    ) {}

    /**
     * @return list<float>|null
     */
    public function embed(string $text, AgentSettings $settings): ?array
    {
        $backend = $settings->embeddingBackend;
        $parts = explode(':', $backend, 2);
        $driver = $parts[0] ?? 'openai';
        $model = $parts[1] ?? match ($driver) {
            'openai' => 'text-embedding-3-small',
            'ollama' => 'nomic-embed-text',
            default => 'text-embedding-3-small',
        };

        try {
            return match ($driver) {
                'openai' => $this->openai->createEmbedding($text, $model),
                'ollama' => $this->ollama->createEmbedding($text, $model),
                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }
}
