<?php

namespace App\Services\Marketplace;

/**
 * Canonical manifest shape (schema_version 1) for documentation and GET /marketplace/manifest-schema.
 */
class SkillManifestSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'schema_version' => 1,
            'name' => 'snake_case tool name, unique',
            'display_name' => 'Human title',
            'description' => 'Shown to the model',
            'category' => 'e.g. productivity, finance, custom',
            'version' => 'semver string for custom skills (optional)',
            'parameters_schema' => [
                'type' => 'object',
                'description' => 'JSON Schema for tool inputs (Anthropic/OpenAI tool format)',
                'properties' => [],
                'required' => [],
            ],
            'rate_limit_per_minute' => 'optional integer, default from config',
            'execution' => [
                'native' => [
                    'type' => 'native',
                    'tool_name' => 'must match a PHP-registered tool name',
                ],
                'http_webhook' => [
                    'type' => 'http_webhook',
                    'endpoint' => 'https URL',
                    'method' => 'GET|POST|PUT|PATCH',
                    'headers' => 'optional key-value map',
                    'timeout_seconds' => 'optional int',
                ],
            ],
        ];
    }
}
