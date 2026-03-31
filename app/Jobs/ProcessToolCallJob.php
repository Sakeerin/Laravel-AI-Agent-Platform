<?php

namespace App\Jobs;

use App\Models\TaskLog;
use App\Models\ToolCall;
use App\Models\User;
use App\Services\Tools\ToolContext;
use App\Services\Tools\ToolExecutor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessToolCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly int $toolCallId,
        private readonly int $userId,
        private readonly ?int $taskLogId = null,
    ) {}

    public function handle(ToolExecutor $executor): void
    {
        $toolCall = ToolCall::findOrFail($this->toolCallId);
        $user = User::findOrFail($this->userId);
        $context = new ToolContext($user, $toolCall->conversation);

        $taskLog = $this->taskLogId ? TaskLog::find($this->taskLogId) : null;
        $taskLog?->markRunning();

        $result = $executor->execute(
            $toolCall->skill_name,
            $toolCall->arguments,
            $toolCall,
            $context
        );

        if ($result['success']) {
            $taskLog?->markCompleted(['result' => $result['result']]);
        } else {
            $taskLog?->markFailed($result['result']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $toolCall = ToolCall::find($this->toolCallId);
        $toolCall?->markFailed($exception->getMessage(), 0);

        $taskLog = $this->taskLogId ? TaskLog::find($this->taskLogId) : null;
        $taskLog?->markFailed($exception->getMessage());
    }
}
