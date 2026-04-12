<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'parameters_schema',
        'category',
        'is_enabled',
        'is_system',
        'config',
        'timeout_seconds',
        'requires_approval',
        'source',
        'skill_package_id',
        'manifest_version',
        'permissions',
        'rate_limit_per_minute',
    ];

    protected function casts(): array
    {
        return [
            'parameters_schema' => 'array',
            'config' => 'array',
            'is_enabled' => 'boolean',
            'is_system' => 'boolean',
            'requires_approval' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function toolCalls(): HasMany
    {
        return $this->hasMany(ToolCall::class, 'skill_name', 'name');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SkillPackage::class, 'skill_package_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SkillRevision::class);
    }

    public function isHttpWebhook(): bool
    {
        return ($this->config['handler'] ?? null) === 'http_webhook';
    }

    public function toToolDefinition(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'input_schema' => $this->parameters_schema,
        ];
    }
}
