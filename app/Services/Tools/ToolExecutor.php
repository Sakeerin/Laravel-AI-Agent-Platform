<?php

namespace App\Services\Tools;

use App\Models\ToolCall;
use Illuminate\Support\Facades\Log;

class ToolExecutor
{
    public function __construct(
        private readonly ToolRegistry $registry,
    ) {}

    /**
     * Execute a tool and record the result.
     */
    public function execute(string $toolName, array $arguments, ToolCall $toolCall, ?ToolContext $context = null): array
    {
        $toolCall->markRunning();
        $startTime = microtime(true);

        try {
            $tool = $this->registry->get($toolName);

            $result = $tool->execute($arguments, $context);
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($result['success']) {
                $resultString = is_string($result['result'])
                    ? $result['result']
                    : json_encode($result['result'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                $toolCall->markCompleted($resultString, $durationMs);

                return [
                    'success' => true,
                    'result' => $resultString,
                    'duration_ms' => $durationMs,
                ];
            }

            $error = $result['error'] ?? 'Tool execution failed';
            $toolCall->markFailed($error, $durationMs);

            return [
                'success' => false,
                'result' => "Error: {$error}",
                'duration_ms' => $durationMs,
            ];

        } catch (\Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::error("Tool execution failed: {$toolName}", [
                'error' => $e->getMessage(),
                'arguments' => $arguments,
            ]);

            $toolCall->markFailed($e->getMessage(), $durationMs);

            return [
                'success' => false,
                'result' => "Error executing tool: {$e->getMessage()}",
                'duration_ms' => $durationMs,
            ];
        }
    }
}
