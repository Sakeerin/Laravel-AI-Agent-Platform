<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $keys = $request->user()->apiKeys()
            ->select('id', 'provider', 'name', 'is_active', 'last_used_at', 'created_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($keys);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:anthropic,openai,ollama'],
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string'],
        ]);

        $apiKey = $request->user()->apiKeys()->create([
            'provider' => $validated['provider'],
            'name' => $validated['name'],
            'key_encrypted' => '',
        ]);

        $apiKey->key = $validated['key'];
        $apiKey->save();

        return response()->json([
            'id' => $apiKey->id,
            'provider' => $apiKey->provider,
            'name' => $apiKey->name,
            'is_active' => $apiKey->is_active,
            'created_at' => $apiKey->created_at,
        ], 201);
    }

    public function destroy(Request $request, ApiKey $apiKey): JsonResponse
    {
        abort_if($apiKey->user_id !== $request->user()->id, 403);

        $apiKey->delete();

        return response()->json(null, 204);
    }
}
