<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conversations = $request->user()
            ->conversations()
            ->orderByDesc('last_activity_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($conversations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'model' => ['sometimes', 'string', 'max:100'],
        ]);

        $conversation = $request->user()->conversations()->create([
            'title' => $validated['title'] ?? 'New Conversation',
            'model' => $validated['model'] ?? config('services.ai.default_model'),
            'last_activity_at' => now(),
        ]);

        return response()->json($conversation, 201);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize($request, $conversation);

        $conversation->load(['messages' => fn($q) => $q->orderBy('created_at')]);

        return response()->json($conversation);
    }

    public function update(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize($request, $conversation);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'model' => ['sometimes', 'string', 'max:100'],
        ]);

        $conversation->update($validated);

        return response()->json($conversation);
    }

    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize($request, $conversation);

        $conversation->delete();

        return response()->json(null, 204);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize($request, $conversation);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->paginate(50);

        return response()->json($messages);
    }

    private function authorize(Request $request, Conversation $conversation): void
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);
    }
}
