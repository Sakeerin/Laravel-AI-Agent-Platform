<?php

namespace App\Services\Marketplace;

use App\Services\Tools\ToolRegistry;
use InvalidArgumentException;

class SkillManifestValidator
{
    public function __construct(
        private readonly ToolRegistry $toolRegistry,
    ) {}

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed> Normalized manifest
     */
    public function validate(array $manifest): array
    {
        if (($manifest['schema_version'] ?? null) !== 1) {
            throw new InvalidArgumentException('manifest.schema_version must be 1.');
        }

        foreach (['name', 'display_name', 'description', 'category', 'parameters_schema', 'execution'] as $key) {
            if (! array_key_exists($key, $manifest)) {
                throw new InvalidArgumentException("manifest.{$key} is required.");
            }
        }

        if (! is_string($manifest['name']) || ! preg_match('/^[a-z][a-z0-9_]*$/', $manifest['name'])) {
            throw new InvalidArgumentException('manifest.name must be snake_case alphanumeric.');
        }

        if (! is_array($manifest['parameters_schema']) || ($manifest['parameters_schema']['type'] ?? '') !== 'object') {
            throw new InvalidArgumentException('manifest.parameters_schema must be a JSON schema object type.');
        }

        $execution = $manifest['execution'];
        if (! is_array($execution) || empty($execution['type'])) {
            throw new InvalidArgumentException('manifest.execution.type is required.');
        }

        $type = $execution['type'];
        if ($type === 'native') {
            $toolName = $execution['tool_name'] ?? null;
            if (! is_string($toolName) || ! $this->toolRegistry->has($toolName)) {
                throw new InvalidArgumentException('manifest.execution.tool_name must reference a registered native tool.');
            }

            return $manifest;
        }

        if ($type === 'http_webhook') {
            foreach (['endpoint', 'method'] as $key) {
                if (empty($execution[$key]) || ! is_string($execution[$key])) {
                    throw new InvalidArgumentException("manifest.execution.{$key} is required for http_webhook.");
                }
            }

            $method = strtoupper($execution['method']);
            if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH'], true)) {
                throw new InvalidArgumentException('manifest.execution.method must be GET, POST, PUT, or PATCH.');
            }

            $allowedHosts = config('skills.http_webhook_allowed_hosts', []);
            if (is_array($allowedHosts) && $allowedHosts !== []) {
                $host = parse_url($execution['endpoint'], PHP_URL_HOST);
                if (! is_string($host) || ! in_array($host, $allowedHosts, true)) {
                    throw new InvalidArgumentException('Webhook endpoint host is not on the allow list. Configure SKILLS_HTTP_WEBHOOK_ALLOWED_HOSTS.');
                }
            }

            return $manifest;
        }

        throw new InvalidArgumentException('manifest.execution.type must be native or http_webhook.');
    }
}
