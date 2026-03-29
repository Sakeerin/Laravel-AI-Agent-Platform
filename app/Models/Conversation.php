<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'model',
        'settings',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'last_activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function lastMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }

    public function touchActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function generateTitle(string $firstMessage): void
    {
        $title = mb_substr($firstMessage, 0, 80);
        if (mb_strlen($firstMessage) > 80) {
            $title .= '...';
        }
        $this->update(['title' => $title]);
    }
}
