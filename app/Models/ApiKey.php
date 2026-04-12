<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'name',
        'key_encrypted',
        'is_active',
        'last_used_at',
    ];

    protected $hidden = [
        'key_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'key_encrypted' => 'encrypted',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
