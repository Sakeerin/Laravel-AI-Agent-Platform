<?php

namespace App\Models;

use App\Events\TaskStatusUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskLog extends Model
{
    protected $fillable = [
        'user_id',
        'conversation_id',
        'type',
        'title',
        'description',
        'status',
        'payload',
        'result',
        'error',
        'progress',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
            'progress' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function markRunning(): void
    {
        $this->update(['status' => 'running', 'started_at' => now()]);
        $this->broadcastStatus();
    }

    public function markCompleted(array $result = []): void
    {
        $this->update([
            'status' => 'completed',
            'result' => $result,
            'progress' => 100,
            'completed_at' => now(),
        ]);
        $this->broadcastStatus();
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error' => $error,
            'completed_at' => now(),
        ]);
        $this->broadcastStatus();
    }

    public function updateProgress(int $progress): void
    {
        $this->update(['progress' => min(100, $progress)]);
        $this->broadcastStatus();
    }

    private function broadcastStatus(): void
    {
        try {
            TaskStatusUpdated::dispatch($this);
        } catch (\Throwable $e) {
            // Broadcasting is optional; don't fail the operation if unavailable
        }
    }
}
