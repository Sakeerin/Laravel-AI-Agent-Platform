<?php

namespace App\Services\Analytics;

use App\Models\UsageEvent;
use App\Models\User;

final class UsageRecorder
{
    public function __construct(
        private readonly TokenCostEstimator $costEstimator,
    ) {}

    public function recordAssistantTurn(
        User $user,
        ?int $conversationId,
        string $model,
        int $inputTokens,
        int $outputTokens,
        string $source = 'web',
    ): void {
        if ($inputTokens <= 0 && $outputTokens <= 0) {
            return;
        }

        $usd = $this->costEstimator->estimateUsd($model, $inputTokens, $outputTokens);

        UsageEvent::query()->create([
            'user_id' => $user->id,
            'conversation_id' => $conversationId,
            'model' => mb_substr($model, 0, 120),
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'estimated_cost_usd' => $usd,
            'source' => mb_substr($source, 0, 32),
        ]);
    }
}
