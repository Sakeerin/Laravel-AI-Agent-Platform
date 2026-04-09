<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMemory extends Model
{
    protected $hidden = [
        'embedding',
    ];

    protected $fillable = [
        'user_id',
        'content',
        'embedding',
        'importance',
        'conversation_id',
        'dedupe_hash',
        'source',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'importance' => 'float',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
