<?php

namespace App\Support;

use App\Models\User;

final class AgentSettings
{
    public function __construct(
        public ?string $persona,
        public bool $memoryEnabled,
        public bool $memoryAutoExtract,
        public int $memoryTopK,
        public float $memoryMinScore,
        public int $contextMaxMessages,
        public bool $heartbeatEnabled,
        public string $embeddingBackend,
        public string $extractionModel,
        public string $heartbeatModel,
    ) {}

    public static function forUser(?User $user): self
    {
        $j = is_array($user?->agent_settings) ? $user->agent_settings : [];

        return new self(
            persona: isset($j['persona']) && is_string($j['persona']) ? $j['persona'] : null,
            memoryEnabled: (bool) ($j['memory_enabled'] ?? true),
            memoryAutoExtract: (bool) ($j['memory_auto_extract'] ?? true),
            memoryTopK: max(1, min(20, (int) ($j['memory_top_k'] ?? 5))),
            memoryMinScore: max(0.0, min(1.0, (float) ($j['memory_min_score'] ?? 0.3))),
            contextMaxMessages: max(4, min(200, (int) ($j['context_max_messages'] ?? 50))),
            heartbeatEnabled: (bool) ($j['heartbeat_enabled'] ?? false),
            embeddingBackend: is_string($j['embedding_backend'] ?? null)
                ? $j['embedding_backend']
                : config('services.memory.default_embedding_backend', 'openai:text-embedding-3-small'),
            extractionModel: is_string($j['extraction_model'] ?? null)
                ? $j['extraction_model']
                : config('services.memory.extraction_model', 'gpt-4o-mini'),
            heartbeatModel: is_string($j['heartbeat_model'] ?? null)
                ? $j['heartbeat_model']
                : config('services.memory.heartbeat_model', 'gpt-4o-mini'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'persona' => $this->persona,
            'memory_enabled' => $this->memoryEnabled,
            'memory_auto_extract' => $this->memoryAutoExtract,
            'memory_top_k' => $this->memoryTopK,
            'memory_min_score' => $this->memoryMinScore,
            'context_max_messages' => $this->contextMaxMessages,
            'heartbeat_enabled' => $this->heartbeatEnabled,
            'embedding_backend' => $this->embeddingBackend,
            'extraction_model' => $this->extractionModel,
            'heartbeat_model' => $this->heartbeatModel,
        ];
    }

    /**
     * @param  array<string, mixed>  $patch
     * @return array<string, mixed>
     */
    public static function merge(User $user, array $patch): array
    {
        $current = is_array($user->agent_settings) ? $user->agent_settings : [];
        $merged = array_merge($current, array_intersect_key($patch, array_flip([
            'persona', 'memory_enabled', 'memory_auto_extract', 'memory_top_k', 'memory_min_score',
            'context_max_messages', 'heartbeat_enabled', 'embedding_backend', 'extraction_model', 'heartbeat_model',
        ])));

        return $merged;
    }
}
