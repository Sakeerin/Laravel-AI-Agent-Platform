<?php

namespace App\Events;

use App\Models\TaskLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TaskLog $taskLog,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->taskLog->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->taskLog->id,
            'status' => $this->taskLog->status,
            'progress' => $this->taskLog->progress,
            'result' => $this->taskLog->result,
            'error' => $this->taskLog->error,
            'started_at' => $this->taskLog->started_at?->toISOString(),
            'completed_at' => $this->taskLog->completed_at?->toISOString(),
        ];
    }
}
