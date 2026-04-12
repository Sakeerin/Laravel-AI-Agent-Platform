<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillPackage extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'slug',
        'title',
        'description',
        'category',
        'version',
        'tags',
        'icon',
        'is_premium',
        'is_featured',
        'manifest',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'manifest' => 'array',
            'is_premium' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class, 'skill_package_id');
    }

    public function installers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skill_installs')
            ->withPivot(['skill_id', 'installed_version'])
            ->withTimestamps();
    }
}
