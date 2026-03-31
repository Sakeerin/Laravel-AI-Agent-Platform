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
        $skills = Skill::orderBy('category')
            ->orderBy('display_name')
            ->get()
            ->map(fn(Skill $skill) => [
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
