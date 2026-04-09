<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserMemory;
use App\Services\Memory\EmbeddingService;
use App\Support\AgentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $memories = $request->user()->memories()
            ->orderByDesc('id')
            ->paginate(40);

        return response()->json($memories);
    }

    public function store(Request $request, EmbeddingService $embedding): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:3', 'max:2000'],
            'importance' => ['sometimes', 'numeric', 'min:0', 'max:1'],
        ]);

        $user = $request->user();
        $settings = AgentSettings::forUser($user);

        $content = trim($validated['content']);
        $hash = hash('sha256', mb_strtolower($content));

        if (UserMemory::query()->where('user_id', $user->id)->where('dedupe_hash', $hash)->exists()) {
            return response()->json(['message' => 'A similar memory already exists.'], 422);
        }

        $vector = $embedding->embed(mb_substr($content, 0, 8000), $settings);
        if ($vector === null) {
            return response()->json(['message' => 'Could not generate embedding. Check embedding_backend and API keys.'], 422);
        }

        $memory = $user->memories()->create([
            'content' => $content,
            'embedding' => $vector,
            'importance' => (float) ($validated['importance'] ?? 0.65),
            'dedupe_hash' => $hash,
            'source' => 'manual',
            'meta' => [],
        ]);

        return response()->json($memory, 201);
    }

    public function destroy(Request $request, UserMemory $userMemory): JsonResponse
    {
        abort_if($userMemory->user_id !== $request->user()->id, 403);

        $userMemory->delete();

        return response()->json(null, 204);
    }
}
