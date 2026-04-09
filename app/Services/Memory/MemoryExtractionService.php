<?php

namespace App\Services\Memory;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\UserMemory;
use App\Services\AI\AIManager;
use App\Support\AgentSettings;
use Illuminate\Support\Facades\Log;

class MemoryExtractionService
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly EmbeddingService $embedding,
    ) {}

    public function extractAndStore(Conversation $conversation): void
    {
        $user = $conversation->user;
        if (! $user) {
            return;
        }

        $settings = AgentSettings::forUser($user);
        if (! $settings->memoryEnabled || ! $settings->memoryAutoExtract) {
            return;
        }

        $messages = $conversation->messages()
            ->orderByDesc('created_at')
            ->limit(24)
            ->get()
            ->sortBy('created_at')
            ->values();

        if ($messages->count() < 2) {
            return;
        }

        $transcript = $messages->map(fn (Message $m) => strtoupper($m->role).': '.mb_substr($m->content, 0, 2000))->implode("\n");

        $extractMessages = [
            [
                'role' => 'system',
                'content' => 'You extract concise, durable user-specific facts for a personal assistant memory bank (preferences, names, long-running goals, habits, things the user said they need to follow up on). '
                    .'Reply with ONLY valid JSON, no markdown: {"memories":["..."]}. Max 5 strings; use empty array if nothing new is worth storing. Each string one standalone fact, under 300 characters.',
            ],
            [
                'role' => 'user',
                'content' => "Conversation excerpt:\n\n{$transcript}",
            ],
        ];

        try {
            $result = $this->aiManager->chat($extractMessages, $settings->extractionModel, ['temperature' => 0.2, 'max_tokens' => 500]);
            $raw = trim($result['content'] ?? '');
            $parsed = json_decode($raw, true);
            if (! is_array($parsed) || ! isset($parsed['memories']) || ! is_array($parsed['memories'])) {
                return;
            }

            foreach ($parsed['memories'] as $item) {
                if (! is_string($item)) {
                    continue;
                }
                $content = trim($item);
                if (mb_strlen($content) < 8) {
                    continue;
                }
                $content = mb_substr($content, 0, 500);
                $hash = hash('sha256', mb_strtolower($content));

                if (UserMemory::query()->where('user_id', $user->id)->where('dedupe_hash', $hash)->exists()) {
                    continue;
                }

                $embedding = $this->embedding->embed($content, $settings);
                if ($embedding === null) {
                    continue;
                }

                if ($this->isTooSimilar($user->id, $embedding)) {
                    continue;
                }

                UserMemory::create([
                    'user_id' => $user->id,
                    'content' => $content,
                    'embedding' => $embedding,
                    'importance' => 0.55,
                    'dedupe_hash' => $hash,
                    'source' => 'auto',
                    'conversation_id' => $conversation->id,
                    'meta' => ['model' => $settings->extractionModel],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('memory_extraction_failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  list<float>  $embedding
     */
    private function isTooSimilar(int $userId, array $embedding): bool
    {
        $recent = UserMemory::query()
            ->where('user_id', $userId)
            ->whereNotNull('embedding')
            ->orderByDesc('id')
            ->limit(80)
            ->get(['embedding']);

        foreach ($recent as $row) {
            if (! is_array($row->embedding)) {
                continue;
            }
            if (VectorCosine::similarity($embedding, $row->embedding) >= 0.93) {
                return true;
            }
        }

        return false;
    }
}
