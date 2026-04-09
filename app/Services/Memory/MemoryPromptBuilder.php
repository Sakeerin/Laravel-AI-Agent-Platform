<?php

namespace App\Services\Memory;

use App\Models\User;
use App\Models\UserMemory;
use App\Support\AgentSettings;

class MemoryPromptBuilder
{
    public function __construct(
        private readonly EmbeddingService $embedding,
    ) {}

    public function recallBlock(User $user, string $latestUserMessage): string
    {
        $settings = AgentSettings::forUser($user);
        if (! $settings->memoryEnabled || trim($latestUserMessage) === '') {
            return '';
        }

        $vector = $this->embedding->embed(mb_substr($latestUserMessage, 0, 8000), $settings);
        if ($vector === null) {
            return '';
        }

        $candidates = UserMemory::query()
            ->where('user_id', $user->id)
            ->whereNotNull('embedding')
            ->orderByDesc('id')
            ->limit(400)
            ->get(['id', 'content', 'embedding', 'importance']);

        $scored = [];
        foreach ($candidates as $row) {
            $emb = $row->embedding;
            if (! is_array($emb) || $emb === []) {
                continue;
            }
            $score = VectorCosine::similarity($vector, $emb) * (0.5 + 0.5 * (float) $row->importance);
            if ($score >= $settings->memoryMinScore) {
                $scored[] = [$score, $row->content];
            }
        }

        usort($scored, fn (array $x, array $y) => $y[0] <=> $x[0]);
        $lines = [];
        foreach (array_slice($scored, 0, $settings->memoryTopK) as [, $content]) {
            $content = trim((string) $content);
            if ($content !== '') {
                $lines[] = '- '.$content;
            }
        }

        if ($lines === []) {
            return '';
        }

        return "Relevant long-term memory about this user (use only if it genuinely helps the reply; do not invent details):\n"
            .implode("\n", $lines);
    }
}
