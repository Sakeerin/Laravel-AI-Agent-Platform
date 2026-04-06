<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChannelConnection extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'webhook_key',
        'label',
        'credentials',
        'is_enabled',
    ];

    protected $hidden = [
        'credentials',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'is_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChannelConnection $model): void {
            if (empty($model->webhook_key)) {
                $model->webhook_key = (string) Str::uuid();
            }
        });

        static::deleting(function (ChannelConnection $model): void {
            $conversationIds = $model->threads()->pluck('conversation_id');
            $model->threads()->delete();
            if ($conversationIds->isNotEmpty()) {
                Conversation::whereIn('id', $conversationIds)->delete();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function threads(): HasMany
    {
        return $this->hasMany(ChannelThread::class);
    }

    public function webhookUrl(): string
    {
        return match ($this->provider) {
            'line' => url("/api/webhooks/line/{$this->webhook_key}"),
            'telegram' => url("/api/webhooks/telegram/{$this->webhook_key}"),
            'slack' => url("/api/webhooks/slack/{$this->webhook_key}"),
            'discord' => url("/api/webhooks/discord/{$this->webhook_key}"),
            default => '',
        };
    }

    public function lineChannelSecret(): ?string
    {
        return $this->credentials['channel_secret'] ?? null;
    }

    public function lineChannelAccessToken(): ?string
    {
        return $this->credentials['channel_access_token'] ?? null;
    }

    public function telegramBotToken(): ?string
    {
        return $this->credentials['bot_token'] ?? null;
    }

    public function telegramWebhookSecret(): ?string
    {
        return $this->credentials['webhook_secret'] ?? null;
    }

    public function slackSigningSecret(): ?string
    {
        return $this->credentials['signing_secret'] ?? null;
    }

    public function slackBotToken(): ?string
    {
        return $this->credentials['bot_token'] ?? null;
    }

    public function discordPublicKey(): ?string
    {
        return $this->credentials['public_key'] ?? null;
    }

    public function discordBotToken(): ?string
    {
        return $this->credentials['bot_token'] ?? null;
    }

    public function discordApplicationId(): ?string
    {
        return $this->credentials['application_id'] ?? null;
    }
}
