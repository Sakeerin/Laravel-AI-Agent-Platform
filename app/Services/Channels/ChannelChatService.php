<?php

namespace App\Services\Channels;

use App\Jobs\ExtractMemoriesFromConversationJob;
use App\Models\ChannelConnection;
use App\Models\ChannelThread;
use App\Models\Conversation;
use App\Models\User;
use App\Services\AI\AgentOrchestrator;
use App\Services\Analytics\UsageRecorder;
use App\Services\Security\PromptInjectionGuard;
use App\Services\Tools\ToolContext;

class ChannelChatService
{
    public function __construct(
        private readonly AgentOrchestrator $orchestrator,
        private readonly PromptInjectionGuard $promptGuard,
        private readonly UsageRecorder $usageRecorder,
    ) {}

    public function replyToText(ChannelConnection $connection, string $externalThreadId, string $text): string
    {
        $guard = $this->promptGuard->check($text);
        if (! $guard['allowed']) {
            return 'Sorry, that message could not be processed due to security restrictions.';
        }
        $text = $guard['content'];

        $user = $connection->user;
        $conversation = $this->resolveConversation($connection, $externalThreadId, $user);

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $text,
        ]);

        if ($conversation->messages()->count() === 1) {
            $conversation->generateTitle($text);
        }

        $conversation->touchActivity();

        $context = new ToolContext($user, $conversation);
        $model = $conversation->model;

        $result = $this->orchestrator->chat($conversation, $model, $context);

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $result['content'],
            'model' => $result['model'] ?? $model,
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
            'metadata' => ! empty($result['tool_calls']) ? ['tool_calls' => $result['tool_calls']] : null,
        ]);

        ExtractMemoriesFromConversationJob::dispatch($conversation->id);

        $this->usageRecorder->recordAssistantTurn(
            $user,
            $conversation->id,
            (string) ($result['model'] ?? $model),
            (int) ($result['input_tokens'] ?? 0),
            (int) ($result['output_tokens'] ?? 0),
            $connection->provider,
        );

        return $result['content'];
    }

    private function resolveConversation(ChannelConnection $connection, string $externalThreadId, User $user): Conversation
    {
        $thread = ChannelThread::query()
            ->where('channel_connection_id', $connection->id)
            ->where('external_thread_id', $externalThreadId)
            ->first();

        if ($thread) {
            return $thread->conversation;
        }

        $conversation = $user->conversations()->create([
            'title' => 'New Conversation',
            'model' => config('services.ai.default_model'),
            'last_activity_at' => now(),
        ]);

        ChannelThread::create([
            'channel_connection_id' => $connection->id,
            'external_thread_id' => $externalThreadId,
            'conversation_id' => $conversation->id,
        ]);

        return $conversation;
    }
}
