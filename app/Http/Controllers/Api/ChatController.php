<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExtractMemoriesFromConversationJob;
use App\Models\Conversation;
use App\Services\AI\AgentOrchestrator;
use App\Services\Analytics\UsageRecorder;
use App\Services\Security\PromptInjectionGuard;
use App\Services\Tools\ToolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly AgentOrchestrator $orchestrator,
        private readonly PromptInjectionGuard $promptGuard,
        private readonly UsageRecorder $usageRecorder,
    ) {}

    public function send(Request $request): JsonResponse|StreamedResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:32000'],
            'conversation_id' => ['sometimes', 'nullable', 'integer', 'exists:conversations,id'],
            'model' => ['sometimes', 'string', 'max:100'],
            'stream' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $shouldStream = $validated['stream'] ?? true;

        $guard = $this->promptGuard->check($validated['message']);
        if (! $guard['allowed']) {
            throw ValidationException::withMessages([
                'message' => ['This message was blocked for security reasons.'],
            ]);
        }
        $messageText = $guard['content'];

        $conversation = $this->resolveConversation($user, $validated);
        $model = $validated['model'] ?? $conversation->model;

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $messageText,
        ]);

        if ($conversation->messages()->count() === 1) {
            $conversation->generateTitle($messageText);
        }

        $conversation->touchActivity();

        $context = new ToolContext($user, $conversation);

        if ($shouldStream) {
            return $this->streamResponse($conversation, $model, $context);
        }

        return $this->syncResponse($conversation, $model, $context);
    }

    public function stream(Request $request, Conversation $conversation): StreamedResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

        $model = $request->query('model', $conversation->model);
        $context = new ToolContext($request->user(), $conversation);

        return $this->streamResponse($conversation, $model, $context);
    }

    private function resolveConversation($user, array $validated): Conversation
    {
        if (! empty($validated['conversation_id'])) {
            $conversation = Conversation::findOrFail($validated['conversation_id']);
            abort_if($conversation->user_id !== $user->id, 403);

            return $conversation;
        }

        return $user->conversations()->create([
            'title' => 'New Conversation',
            'model' => $validated['model'] ?? config('services.ai.default_model'),
            'last_activity_at' => now(),
        ]);
    }

    private function syncResponse(Conversation $conversation, string $model, ToolContext $context): JsonResponse
    {
        $result = $this->orchestrator->chat($conversation, $model, $context);

        $assistantMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $result['content'],
            'model' => $result['model'] ?? $model,
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
            'metadata' => ! empty($result['tool_calls']) ? ['tool_calls' => $result['tool_calls']] : null,
        ]);

        ExtractMemoriesFromConversationJob::dispatch($conversation->id);

        $this->usageRecorder->recordAssistantTurn(
            $conversation->user,
            $conversation->id,
            (string) ($result['model'] ?? $model),
            (int) ($result['input_tokens'] ?? 0),
            (int) ($result['output_tokens'] ?? 0),
            'web',
        );

        return response()->json([
            'message' => $assistantMessage,
            'conversation_id' => $conversation->id,
            'tool_calls' => $result['tool_calls'] ?? [],
        ]);
    }

    private function streamResponse(Conversation $conversation, string $model, ToolContext $context): StreamedResponse
    {
        return response()->stream(function () use ($conversation, $model, $context) {
            $fullContent = '';
            $inputTokens = 0;
            $outputTokens = 0;
            $toolCalls = [];

            try {
                foreach ($this->orchestrator->stream($conversation, $model, $context) as $chunk) {
                    $type = $chunk['type'];

                    if ($type === 'text') {
                        $fullContent .= $chunk['content'];
                        $this->sendEvent([
                            'type' => 'text',
                            'content' => $chunk['content'],
                            'conversation_id' => $conversation->id,
                        ]);
                    } elseif ($type === 'tool_start') {
                        $this->sendEvent([
                            'type' => 'tool_start',
                            'tool_call_id' => $chunk['tool_call_id'],
                            'tool_name' => $chunk['tool_name'],
                            'arguments' => $chunk['arguments'],
                            'conversation_id' => $conversation->id,
                        ]);
                    } elseif ($type === 'tool_result') {
                        $toolCalls[] = $chunk;
                        $this->sendEvent([
                            'type' => 'tool_result',
                            'tool_call_id' => $chunk['tool_call_id'],
                            'tool_name' => $chunk['tool_name'],
                            'result' => $chunk['result'],
                            'success' => $chunk['success'],
                            'duration_ms' => $chunk['duration_ms'],
                            'conversation_id' => $conversation->id,
                        ]);
                    } elseif ($type === 'done') {
                        $fullContent = $chunk['full_content'] ?? $fullContent;
                        $inputTokens = $chunk['input_tokens'] ?? 0;
                        $outputTokens = $chunk['output_tokens'] ?? 0;
                    }
                }

                $assistantMessage = $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $fullContent,
                    'model' => $model,
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'metadata' => ! empty($toolCalls) ? ['tool_calls' => $toolCalls] : null,
                ]);

                $this->sendEvent([
                    'type' => 'done',
                    'message_id' => $assistantMessage->id,
                    'conversation_id' => $conversation->id,
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'tool_calls_count' => count($toolCalls),
                ]);

                ExtractMemoriesFromConversationJob::dispatch($conversation->id);

                $conversation->loadMissing('user');
                if ($conversation->user) {
                    $this->usageRecorder->recordAssistantTurn(
                        $conversation->user,
                        $conversation->id,
                        $model,
                        $inputTokens,
                        $outputTokens,
                        'web',
                    );
                }

            } catch (\Exception $e) {
                $this->sendEvent([
                    'type' => 'error',
                    'content' => 'An error occurred: '.$e->getMessage(),
                ]);
            }

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function sendEvent(array $data): void
    {
        echo 'data: '.json_encode($data)."\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
