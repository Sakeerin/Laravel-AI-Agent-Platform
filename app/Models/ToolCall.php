<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolCall extends Model
{
    protected $fillable = [
        'message_id',
        'conversation_id',
        'tool_call_id',
        'skill_name',
        'arguments',
        'result',
        'status',
        'error',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'duration_ms' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function markRunning(): void
    {
        $this->update(['status' => 'running']);
    }

    public function markCompleted(string $result, int $durationMs): void
    {
        $this->update([
            'status' => 'completed',
            'result' => $result,
            'duration_ms' => $durationMs,
        ]);
    }

    public function markFailed(string $error, int $durationMs): void
    {
        $this->update([
            'status' => 'failed',
            'error' => $error,
            'duration_ms' => $durationMs,
        ]);
    }
}
