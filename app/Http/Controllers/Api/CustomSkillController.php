<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\SkillRevision;
use App\Services\Marketplace\SkillManifestValidator;
use App\Services\Marketplace\YamlManifestParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomSkillController extends Controller
{
    public function store(Request $request, SkillManifestValidator $validator): JsonResponse
    {
        $resolved = $this->resolveManifestInput($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        try {
            $manifest = $validator->validate($resolved);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (($manifest['execution']['type'] ?? '') !== 'http_webhook') {
            return response()->json([
                'message' => 'Custom skills must use execution.type http_webhook.',
            ], 422);
        }

        $exec = $manifest['execution'];
        $skill = DB::transaction(function () use ($manifest, $exec) {
            $skill = Skill::query()->create([
                'name' => $manifest['name'],
                'display_name' => $manifest['display_name'],
                'description' => $manifest['description'],
                'parameters_schema' => $manifest['parameters_schema'],
                'category' => $manifest['category'],
                'is_enabled' => true,
                'is_system' => false,
                'requires_approval' => false,
                'timeout_seconds' => (int) ($exec['timeout_seconds'] ?? 30),
                'config' => [
                    'handler' => 'http_webhook',
                    'http' => [
                        'url' => $exec['endpoint'],
                        'method' => strtoupper((string) $exec['method']),
                        'headers' => is_array($exec['headers'] ?? null) ? $exec['headers'] : [],
                        'timeout_seconds' => (int) ($exec['timeout_seconds'] ?? 30),
                    ],
                ],
                'permissions' => ['network'],
                'rate_limit_per_minute' => (int) ($manifest['rate_limit_per_minute'] ?? 30),
                'source' => 'custom',
                'manifest_version' => (string) ($manifest['version'] ?? '1.0.0'),
            ]);

            return $skill;
        });

        return response()->json($skill, 201);
    }

    public function update(Request $request, Skill $skill, SkillManifestValidator $validator): JsonResponse
    {
        if ($skill->source !== 'custom') {
            return response()->json(['message' => 'Only custom webhook skills can be edited here.'], 403);
        }

        $resolved = $this->resolveManifestInput($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        try {
            $manifest = $validator->validate($resolved);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (($manifest['execution']['type'] ?? '') !== 'http_webhook') {
            return response()->json(['message' => 'Custom skills must use http_webhook.'], 422);
        }

        if ($manifest['name'] !== $skill->name) {
            return response()->json(['message' => 'manifest.name cannot be renamed; delete and create a new skill.'], 422);
        }

        $exec = $manifest['execution'];

        DB::transaction(function () use ($skill, $manifest, $exec) {
            SkillRevision::query()->create([
                'skill_id' => $skill->id,
                'version' => (string) ($skill->manifest_version ?? '1.0.0'),
                'snapshot' => [
                    'display_name' => $skill->display_name,
                    'description' => $skill->description,
                    'parameters_schema' => $skill->parameters_schema,
                    'category' => $skill->category,
                    'timeout_seconds' => $skill->timeout_seconds,
                    'config' => $skill->config,
                    'rate_limit_per_minute' => $skill->rate_limit_per_minute,
                    'manifest_version' => $skill->manifest_version,
                ],
            ]);

            $skill->update([
                'display_name' => $manifest['display_name'],
                'description' => $manifest['description'],
                'parameters_schema' => $manifest['parameters_schema'],
                'category' => $manifest['category'],
                'timeout_seconds' => (int) ($exec['timeout_seconds'] ?? 30),
                'config' => [
                    'handler' => 'http_webhook',
                    'http' => [
                        'url' => $exec['endpoint'],
                        'method' => strtoupper((string) $exec['method']),
                        'headers' => is_array($exec['headers'] ?? null) ? $exec['headers'] : [],
                        'timeout_seconds' => (int) ($exec['timeout_seconds'] ?? 30),
                    ],
                ],
                'rate_limit_per_minute' => (int) ($manifest['rate_limit_per_minute'] ?? $skill->rate_limit_per_minute ?? 30),
                'manifest_version' => (string) ($manifest['version'] ?? $skill->manifest_version),
            ]);
        });

        return response()->json($skill->fresh());
    }

    public function destroy(Skill $skill): JsonResponse
    {
        if (! in_array($skill->source, ['custom'], true)) {
            return response()->json(['message' => 'Only custom skills can be deleted this way.'], 403);
        }

        $skill->delete();

        return response()->json(['message' => 'Skill deleted.']);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function resolveManifestInput(Request $request): array|JsonResponse
    {
        $request->validate([
            'manifest' => ['required_without:manifest_yaml', 'array'],
            'manifest_yaml' => ['required_without:manifest', 'string'],
        ]);

        if ($request->filled('manifest') && $request->filled('manifest_yaml')) {
            return response()->json(['message' => 'Send either manifest (JSON) or manifest_yaml, not both.'], 422);
        }

        if ($request->filled('manifest_yaml')) {
            try {
                return YamlManifestParser::parse($request->string('manifest_yaml')->toString());
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        return $request->input('manifest', []);
    }
}
