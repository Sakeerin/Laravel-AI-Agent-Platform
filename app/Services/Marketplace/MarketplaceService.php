<?php

namespace App\Services\Marketplace;

use App\Models\Skill;
use App\Models\SkillPackage;
use App\Models\User;
use App\Models\UserSkillInstall;
use Illuminate\Support\Facades\DB;

class MarketplaceService
{
    public function __construct(
        private readonly SkillManifestValidator $manifestValidator,
    ) {}

    public function install(User $user, SkillPackage $package): Skill
    {
        if ($package->is_premium && ! config('skills.allow_premium_install_without_subscription', true)) {
            abort(402, 'Premium package requires an active subscription.');
        }

        $manifest = $this->manifestValidator->validate($package->manifest);

        return DB::transaction(function () use ($user, $package, $manifest) {
            $skill = $this->materializeSkill($package, $manifest);
            $skill->update([
                'is_enabled' => true,
                'skill_package_id' => $package->id,
                'manifest_version' => $package->version,
                'source' => 'marketplace',
            ]);

            UserSkillInstall::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'skill_package_id' => $package->id,
                ],
                [
                    'skill_id' => $skill->id,
                    'installed_version' => $package->version,
                ]
            );

            return $skill->fresh();
        });
    }

    public function uninstall(User $user, SkillPackage $package): void
    {
        DB::transaction(function () use ($user, $package) {
            UserSkillInstall::query()
                ->where('user_id', $user->id)
                ->where('skill_package_id', $package->id)
                ->delete();

            $remaining = UserSkillInstall::query()
                ->where('skill_package_id', $package->id)
                ->exists();

            if ($remaining) {
                return;
            }

            $manifest = $package->manifest;
            $toolName = $manifest['execution']['tool_name'] ?? $manifest['name'] ?? null;

            if (($manifest['execution']['type'] ?? '') === 'native' && is_string($toolName)) {
                Skill::query()->where('name', $toolName)->update(['is_enabled' => false]);

                return;
            }

            if (($manifest['execution']['type'] ?? '') === 'http_webhook') {
                Skill::query()
                    ->where('skill_package_id', $package->id)
                    ->where('source', 'marketplace')
                    ->delete();
            }
        });
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function materializeSkill(SkillPackage $package, array $manifest): Skill
    {
        if (($manifest['execution']['type'] ?? '') === 'native') {
            $toolName = (string) ($manifest['execution']['tool_name'] ?? $manifest['name']);

            return Skill::query()->where('name', $toolName)->firstOrFail();
        }

        $exec = $manifest['execution'];

        return Skill::query()->updateOrCreate(
            [
                'name' => $manifest['name'],
            ],
            [
                'display_name' => $manifest['display_name'],
                'description' => $manifest['description'],
                'parameters_schema' => $manifest['parameters_schema'],
                'category' => $manifest['category'],
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
                'rate_limit_per_minute' => (int) ($manifest['rate_limit_per_minute'] ?? 60),
                'source' => 'marketplace',
                'skill_package_id' => $package->id,
                'manifest_version' => $package->version,
            ]
        );
    }
}
