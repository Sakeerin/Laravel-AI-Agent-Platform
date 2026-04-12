<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Services\Tools\ToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function index(): JsonResponse
    {
        $skills = Skill::with('package')
            ->orderBy('category')
            ->orderBy('display_name')
            ->get()
            ->map(fn (Skill $skill) => [
                'id' => $skill->id,
                'name' => $skill->name,
                'display_name' => $skill->display_name,
                'description' => $skill->description,
                'category' => $skill->category,
                'is_enabled' => $skill->is_enabled,
                'is_system' => $skill->is_system,
                'requires_approval' => $skill->requires_approval,
                'timeout_seconds' => $skill->timeout_seconds,
                'parameters_schema' => $skill->parameters_schema,
                'source' => $skill->source,
                'manifest_version' => $skill->manifest_version,
                'package_slug' => $skill->package?->slug,
                'rate_limit_per_minute' => $skill->rate_limit_per_minute,
                'is_http_webhook' => $skill->isHttpWebhook(),
            ]);

        return response()->json($skills);
    }

    public function show(Skill $skill): JsonResponse
    {
        return response()->json($skill);
    }

    public function toggle(Request $request, Skill $skill): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
        ]);

        $skill->update(['is_enabled' => $validated['is_enabled']]);

        return response()->json($skill);
    }

    public function rollback(Skill $skill): JsonResponse
    {
        if (! in_array($skill->source, ['custom', 'marketplace'], true)) {
            return response()->json(['message' => 'Rollback is only available for custom or marketplace webhook skills.'], 403);
        }

        if (! $skill->isHttpWebhook()) {
            return response()->json(['message' => 'Rollback applies to HTTP webhook skills with stored revisions.'], 422);
        }

        $revision = $skill->revisions()->orderByDesc('id')->first();
        if (! $revision) {
            return response()->json(['message' => 'No previous revision found.'], 404);
        }

        $snap = $revision->snapshot;
        $skill->update([
            'display_name' => $snap['display_name'] ?? $skill->display_name,
            'description' => $snap['description'] ?? $skill->description,
            'parameters_schema' => $snap['parameters_schema'] ?? $skill->parameters_schema,
            'category' => $snap['category'] ?? $skill->category,
            'timeout_seconds' => $snap['timeout_seconds'] ?? $skill->timeout_seconds,
            'config' => $snap['config'] ?? $skill->config,
            'rate_limit_per_minute' => $snap['rate_limit_per_minute'] ?? $skill->rate_limit_per_minute,
            'manifest_version' => $snap['manifest_version'] ?? $skill->manifest_version,
        ]);
        $revision->delete();

        return response()->json($skill->fresh());
    }

    public function available(ToolRegistry $registry): JsonResponse
    {
        $tools = [];
        foreach ($registry->enabled() as $tool) {
            $tools[] = [
                'name' => $tool->name(),
                'display_name' => $tool->displayName(),
                'description' => $tool->description(),
                'category' => $tool->category(),
                'parameters_schema' => $tool->parametersSchema(),
            ];
        }

        return response()->json($tools);
    }
}
