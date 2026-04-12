<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillRevision extends Model
{
    protected $fillable = [
        'skill_id',
        'version',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
