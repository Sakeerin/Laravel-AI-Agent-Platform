<?php

namespace App\Services\Marketplace;

use App\Models\Skill;
use App\Services\Tools\ToolContext;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class HttpWebhookSkillExecutor
{
    /**
     * @return array{success: bool, result?: string, error?: string}
     */
    public function execute(Skill $skill, array $arguments, ?ToolContext $context = null): array
    {
        $cfg = $skill->config['http'] ?? [];
        $url = $cfg['url'] ?? null;
        $method = strtoupper((string) ($cfg['method'] ?? 'POST'));

        if (! is_string($url) || $url === '') {
            return ['success' => false, 'error' => 'HTTP webhook skill missing config.http.url'];
        }

        $userId = $context?->user->id ?? 0;
        $maxAttempts = max(1, min(600, (int) ($skill->rate_limit_per_minute ?? 60)));

        if ($userId > 0) {
            $key = 'skill-webhook:'.$userId.':'.$skill->id;
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                return [
                    'success' => false,
                    'error' => 'Rate limit exceeded for this skill. Try again shortly.',
                ];
            }
            RateLimiter::hit($key, 60);
        }

        $permissions = $skill->permissions ?? [];
        if ($permissions !== [] && ! in_array('network', $permissions, true)) {
            return ['success' => false, 'error' => 'Skill is missing network permission.'];
        }

        $headers = is_array($cfg['headers'] ?? null) ? $cfg['headers'] : [];
        $timeout = (int) ($cfg['timeout_seconds'] ?? config('skills.http_webhook_timeout_seconds', 30));

        try {
            $pending = Http::timeout($timeout)
                ->withHeaders(array_merge(['Accept' => 'application/json'], $headers));

            $response = match ($method) {
                'GET' => $pending->get($url, $arguments),
                'POST' => $pending->post($url, $arguments),
                'PUT' => $pending->put($url, $arguments),
                'PATCH' => $pending->patch($url, $arguments),
                default => $pending->post($url, $arguments),
            };

            $body = $response->body();
            if (strlen($body) > 50000) {
                $body = substr($body, 0, 50000)."\n...[truncated]";
            }

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Webhook returned HTTP '.$response->status().': '.mb_substr($body, 0, 2000),
                ];
            }

            return ['success' => true, 'result' => $body];

        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
