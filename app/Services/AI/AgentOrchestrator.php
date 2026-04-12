<?php

namespace App\Services\AI;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ToolCall;
use App\Services\Memory\MemoryPromptBuilder;
use App\Services\Tools\ToolContext;
use App\Services\Tools\ToolExecutor;
use App\Services\Tools\ToolRegistry;
use App\Support\AgentSettings;

class AgentOrchestrator
{
    private const MAX_TOOL_ITERATIONS = 10;

    public function __construct(
        private readonly AIManager $aiManager,
        private readonly ToolRegistry $toolRegistry,
        private readonly ToolExecutor $toolExecutor,
        private readonly MemoryPromptBuilder $memoryPromptBuilder,
    ) {}

    /**
     * Run a synchronous chat with tool use support.
     */
    public function chat(Conversation $conversation, string $model, ToolContext $context): array
    {
        $history = $this->buildHistory($conversation);
        $tools = $this->toolRegistry->getToolDefinitions();
        $totalInputTokens = 0;
        $totalOutputTokens = 0;
        $allToolCalls = [];

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $provider = $this->aiManager->resolveProvider($model);
            $result = $provider->chat($history, $model, ['tools' => $tools]);

            $totalInputTokens += $result['input_tokens'] ?? 0;
            $totalOutputTokens += $result['output_tokens'] ?? 0;

            if (empty($result['tool_calls']) || $result['stop_reason'] !== 'tool_use') {
                return [
                    'content' => $result['content'],
                    'model' => $result['model'],
                    'input_tokens' => $totalInputTokens,
                    'output_tokens' => $totalOutputTokens,
                    'tool_calls' => $allToolCalls,
                ];
            }

            $history[] = [
                'role' => 'assistant',
                'content' => $result['raw_content'],
            ];

            $toolResults = [];
            foreach ($result['tool_calls'] as $tc) {
                $toolCall = ToolCall::create([
                    'conversation_id' => $conversation->id,
                    'tool_call_id' => $tc['id'],
                    'skill_name' => $tc['name'],
                    'arguments' => $tc['input'],
                    'status' => 'pending',
                ]);

                $execResult = $this->toolExecutor->execute(
                    $tc['name'], $tc['input'], $toolCall, $context
                );

                $allToolCalls[] = [
                    'id' => $tc['id'],
                    'name' => $tc['name'],
                    'arguments' => $tc['input'],
                    'result' => $execResult['result'],
                    'success' => $execResult['success'],
                    'duration_ms' => $execResult['duration_ms'],
                ];

                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $tc['id'],
                    'content' => $execResult['result'],
                    'is_error' => ! $execResult['success'],
                ];
            }

            $history[] = [
                'role' => 'user',
                'content' => $toolResults,
            ];
        }

        return [
            'content' => 'Maximum tool iterations reached. Here is what I found so far.',
            'model' => $model,
            'input_tokens' => $totalInputTokens,
            'output_tokens' => $totalOutputTokens,
            'tool_calls' => $allToolCalls,
        ];
    }

    /**
     * Run a streaming chat with tool use support.
     * Yields SSE-formatted events for the client.
     */
    public function stream(Conversation $conversation, string $model, ToolContext $context): \Generator
    {
        $history = $this->buildHistory($conversation);
        $tools = $this->toolRegistry->getToolDefinitions();
        $totalInputTokens = 0;
        $totalOutputTokens = 0;

        for ($iteration = 0; $iteration < self::MAX_TOOL_ITERATIONS; $iteration++) {
            $provider = $this->aiManager->resolveProvider($model);
            $textContent = '';
            $toolCalls = [];
            $stopReason = null;

            foreach ($provider->stream($history, $model, ['tools' => $tools]) as $chunk) {
                if ($chunk['type'] === 'text') {
                    $textContent .= $chunk['content'];
                    yield ['type' => 'text', 'content' => $chunk['content']];
                } elseif ($chunk['type'] === 'tool_use') {
                    $toolCalls[] = $chunk['tool_call'];
                    yield [
                        'type' => 'tool_start',
                        'tool_call_id' => $chunk['tool_call']['id'],
                        'tool_name' => $chunk['tool_call']['name'],
                        'arguments' => $chunk['tool_call']['input'],
                    ];
                } elseif ($chunk['type'] === 'done') {
                    $totalInputTokens += $chunk['input_tokens'] ?? 0;
                    $totalOutputTokens += $chunk['output_tokens'] ?? 0;
                    $stopReason = $chunk['stop_reason'] ?? null;
                }
            }

            if (empty($toolCalls) || $stopReason !== 'tool_use') {
                yield [
                    'type' => 'done',
                    'full_content' => $textContent,
                    'input_tokens' => $totalInputTokens,
                    'output_tokens' => $totalOutputTokens,
                ];

                return;
            }

            // Build assistant content for history
            $assistantContent = [];
            if (! empty($textContent)) {
                $assistantContent[] = ['type' => 'text', 'text' => $textContent];
            }
            foreach ($toolCalls as $tc) {
                $assistantContent[] = [
                    'type' => 'tool_use',
                    'id' => $tc['id'],
                    'name' => $tc['name'],
                    'input' => $tc['input'],
                ];
            }

            $history[] = ['role' => 'assistant', 'content' => $assistantContent];

            // Execute tools and feed results back
            $toolResults = [];
            foreach ($toolCalls as $tc) {
                $toolCall = ToolCall::create([
                    'conversation_id' => $conversation->id,
                    'tool_call_id' => $tc['id'],
                    'skill_name' => $tc['name'],
                    'arguments' => $tc['input'],
                    'status' => 'pending',
                ]);

                $execResult = $this->toolExecutor->execute(
                    $tc['name'], $tc['input'], $toolCall, $context
                );

                yield [
                    'type' => 'tool_result',
                    'tool_call_id' => $tc['id'],
                    'tool_name' => $tc['name'],
                    'result' => mb_substr($execResult['result'], 0, 500),
                    'success' => $execResult['success'],
                    'duration_ms' => $execResult['duration_ms'],
                ];

                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $tc['id'],
                    'content' => $execResult['result'],
                    'is_error' => ! $execResult['success'],
                ];
            }

            $history[] = ['role' => 'user', 'content' => $toolResults];

            yield ['type' => 'text', 'content' => "\n\n"];
        }

        yield [
            'type' => 'done',
            'full_content' => 'Maximum tool iterations reached.',
            'input_tokens' => $totalInputTokens,
            'output_tokens' => $totalOutputTokens,
        ];
    }

    private function buildHistory(Conversation $conversation): array
    {
        $conversation->loadMissing('user');

        $settings = AgentSettings::forUser($conversation->user);

        $dbMessages = $conversation->messages()
            ->orderBy('created_at')
            ->get();

        if ($dbMessages->count() > $settings->contextMaxMessages) {
            $dbMessages = $dbMessages->slice(-$settings->contextMaxMessages)->values();
        }

        $messages = $dbMessages->map(fn (Message $m) => [
            'role' => $m->role,
            'content' => $m->content,
        ])->toArray();

        $toolNames = implode(', ', $this->toolRegistry->enabledToolNames());

        $basePrompt = "You are a helpful AI assistant with access to tools (web search, files, shell, browser, calculator, date/time, weather, market data, integrations, and any installed marketplace skills).\n\nAvailable tools: {$toolNames}\n\nUse tools when they would help answer the user's question. Be concise and accurate in your responses.";

        if ($settings->persona !== null && trim($settings->persona) !== '') {
            $basePrompt = trim($settings->persona)."\n\n".$basePrompt;
        }

        $lastUser = $dbMessages->where('role', 'user')->last();
        if ($lastUser && $conversation->user) {
            $memoryBlock = $this->memoryPromptBuilder->recallBlock($conversation->user, $lastUser->content);
            if ($memoryBlock !== '') {
                $basePrompt .= "\n\n".$memoryBlock;
            }
        }

        array_unshift($messages, [
            'role' => 'system',
            'content' => $basePrompt,
        ]);

        return $messages;
    }
}
