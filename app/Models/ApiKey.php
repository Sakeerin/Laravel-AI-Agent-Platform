<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

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
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setKeyAttribute(string $value): void
    {
        $this->attributes['key_encrypted'] = Crypt::encryptString($value);
    }

    public function getDecryptedKeyAttribute(): string
    {
        return Crypt::decryptString($this->key_encrypted);
    }
}
