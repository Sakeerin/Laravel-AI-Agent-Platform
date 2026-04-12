<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkillInstall extends Model
{
    protected $fillable = [
        'user_id',
        'skill_package_id',
        'skill_id',
        'installed_version',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SkillPackage::class, 'skill_package_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
