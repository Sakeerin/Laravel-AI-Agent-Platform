<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\TaskLog;
use App\Models\User;
use App\Services\AI\AgentOrchestrator;
use App\Services\Tools\ToolContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteAgentTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        private readonly int $conversationId,
        private readonly int $userId,
        private readonly string $model,
        private readonly int $taskLogId,
    ) {}

    public function handle(AgentOrchestrator $orchestrator): void
    {
        $conversation = Conversation::findOrFail($this->conversationId);
        $user = User::findOrFail($this->userId);
        $taskLog = TaskLog::findOrFail($this->taskLogId);

        $taskLog->markRunning();
        $context = new ToolContext($user, $conversation);

        try {
            $result = $orchestrator->chat($conversation, $this->model, $context);

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $result['content'],
                'model' => $result['model'] ?? $this->model,
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
                'metadata' => !empty($result['tool_calls']) ? ['tool_calls' => $result['tool_calls']] : null,
            ]);

            $taskLog->markCompleted([
                'content' => mb_substr($result['content'], 0, 500),
                'tool_calls_count' => count($result['tool_calls'] ?? []),
                'tokens' => $result['input_tokens'] + $result['output_tokens'],
            ]);

        } catch (\Exception $e) {
            $taskLog->markFailed($e->getMessage());
            throw $e;
        }
    }
}
