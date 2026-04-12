<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SkillPackage;
use App\Services\Marketplace\MarketplaceService;
use App\Services\Marketplace\SkillManifestSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function manifestSchema(): JsonResponse
    {
        return response()->json([
            'schema_version' => 1,
            'description' => 'Skill package manifest format (JSON or YAML; YAML supported for custom skills via manifest_yaml).',
            'definition' => SkillManifestSchema::definition(),
        ]);
    }

    public function packages(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        $query = SkillPackage::query()->orderByDesc('is_featured')->orderBy('title');

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%')
                    ->orWhere('slug', 'like', '%'.$q.'%');
            });
        }

        $items = $query->get()->map(fn (SkillPackage $p) => [
            'id' => $p->id,
            'slug' => $p->slug,
            'title' => $p->title,
            'description' => $p->description,
            'category' => $p->category,
            'version' => $p->version,
            'tags' => $p->tags ?? [],
            'icon' => $p->icon,
            'is_premium' => $p->is_premium,
            'is_featured' => $p->is_featured,
            'tool_name' => $p->manifest['name'] ?? null,
        ]);

        return response()->json($items);
    }

    public function show(SkillPackage $package): JsonResponse
    {
        return response()->json([
            'id' => $package->id,
            'slug' => $package->slug,
            'title' => $package->title,
            'description' => $package->description,
            'category' => $package->category,
            'version' => $package->version,
            'tags' => $package->tags ?? [],
            'icon' => $package->icon,
            'is_premium' => $package->is_premium,
            'is_featured' => $package->is_featured,
            'manifest' => $package->manifest,
        ]);
    }

    public function install(Request $request, SkillPackage $package, MarketplaceService $marketplace): JsonResponse
    {
        try {
            $skill = $marketplace->install($request->user(), $package);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Skill installed and enabled.',
            'skill' => [
                'id' => $skill->id,
                'name' => $skill->name,
                'display_name' => $skill->display_name,
                'is_enabled' => $skill->is_enabled,
            ],
        ]);
    }

    public function uninstall(Request $request, SkillPackage $package, MarketplaceService $marketplace): JsonResponse
    {
        $marketplace->uninstall($request->user(), $package);

        return response()->json(['message' => 'Skill uninstalled for your workspace policy.']);
    }

    public function myInstalls(Request $request): JsonResponse
    {
        $installs = $request->user()
            ->skillInstalls()
            ->with('package')
            ->get()
            ->map(fn ($row) => [
                'package_slug' => $row->package?->slug,
                'package_title' => $row->package?->title,
                'installed_version' => $row->installed_version,
                'skill_id' => $row->skill_id,
            ]);

        return response()->json($installs);
    }
}
