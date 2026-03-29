<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AI\AIManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly AIManager $ai,
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

        $conversation = $this->resolveConversation($user, $validated);
        $model = $validated['model'] ?? $conversation->model;

        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        if ($conversation->messages()->count() === 1) {
            $conversation->generateTitle($validated['message']);
        }

        $conversation->touchActivity();

        if ($shouldStream) {
            return $this->streamResponse($conversation, $model);
        }

        return $this->syncResponse($conversation, $model);
    }

    public function stream(Request $request, Conversation $conversation): StreamedResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

        $model = $request->query('model', $conversation->model);

        return $this->streamResponse($conversation, $model);
    }

    private function resolveConversation($user, array $validated): Conversation
    {
        if (!empty($validated['conversation_id'])) {
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

    private function buildChatHistory(Conversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn(Message $m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->toArray();

        array_unshift($messages, [
            'role' => 'system',
            'content' => 'You are a helpful AI assistant. Be concise and accurate in your responses.',
        ]);

        return $messages;
    }

    private function syncResponse(Conversation $conversation, string $model): JsonResponse
    {
        $history = $this->buildChatHistory($conversation);
        $result = $this->ai->chat($history, $model);

        $assistantMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $result['content'],
            'model' => $result['model'],
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
        ]);

        return response()->json([
            'message' => $assistantMessage,
            'conversation_id' => $conversation->id,
        ]);
    }

    private function streamResponse(Conversation $conversation, string $model): StreamedResponse
    {
        $history = $this->buildChatHistory($conversation);

        return response()->stream(function () use ($conversation, $history, $model) {
            $fullContent = '';
            $inputTokens = 0;
            $outputTokens = 0;

            try {
                foreach ($this->ai->stream($history, $model) as $chunk) {
                    if ($chunk['type'] === 'text') {
                        $fullContent .= $chunk['content'];
                        echo "data: " . json_encode([
                            'type' => 'text',
                            'content' => $chunk['content'],
                            'conversation_id' => $conversation->id,
                        ]) . "\n\n";
                    } elseif ($chunk['type'] === 'done') {
                        $inputTokens = $chunk['input_tokens'] ?? 0;
                        $outputTokens = $chunk['output_tokens'] ?? 0;
                    }

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                $assistantMessage = $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $fullContent,
                    'model' => $model,
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                ]);

                echo "data: " . json_encode([
                    'type' => 'done',
                    'message_id' => $assistantMessage->id,
                    'conversation_id' => $conversation->id,
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                ]) . "\n\n";

            } catch (\Exception $e) {
                echo "data: " . json_encode([
                    'type' => 'error',
                    'content' => 'An error occurred: ' . $e->getMessage(),
                ]) . "\n\n";
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
}
