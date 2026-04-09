<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AgentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentSettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(AgentSettings::forUser($request->user())->toArray());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'persona' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'memory_enabled' => ['sometimes', 'boolean'],
            'memory_auto_extract' => ['sometimes', 'boolean'],
            'memory_top_k' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'memory_min_score' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'context_max_messages' => ['sometimes', 'integer', 'min:4', 'max:200'],
            'heartbeat_enabled' => ['sometimes', 'boolean'],
            'embedding_backend' => ['sometimes', 'string', 'max:120'],
            'extraction_model' => ['sometimes', 'string', 'max:100'],
            'heartbeat_model' => ['sometimes', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $user->agent_settings = AgentSettings::merge($user, $validated);
        $user->save();

        return response()->json(AgentSettings::forUser($user)->toArray());
    }
}
