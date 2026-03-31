<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
    ];

    protected function casts(): array
    {
        return [
            'parameters_schema' => 'array',
            'config' => 'array',
            'is_enabled' => 'boolean',
            'is_system' => 'boolean',
            'requires_approval' => 'boolean',
        ];
    }

    public function toolCalls(): HasMany
    {
        return $this->hasMany(ToolCall::class, 'skill_name', 'name');
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
