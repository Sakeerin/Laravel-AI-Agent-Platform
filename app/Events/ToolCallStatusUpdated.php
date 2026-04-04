<?php

namespace App\Events;

use App\Models\ToolCall;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ToolCallStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ToolCall $toolCall,
        public readonly int $userId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'tool_call.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->toolCall->id,
            'conversation_id' => $this->toolCall->conversation_id,
            'tool_call_id' => $this->toolCall->tool_call_id,
            'skill_name' => $this->toolCall->skill_name,
            'status' => $this->toolCall->status,
            'duration_ms' => $this->toolCall->duration_ms,
        ];
    }
}
