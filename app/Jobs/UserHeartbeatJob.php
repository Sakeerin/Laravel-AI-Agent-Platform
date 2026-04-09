<?php

namespace App\Jobs;

use App\Models\AgentReminder;
use App\Models\User;
use App\Models\UserMemory;
use App\Services\AI\AIManager;
use App\Support\AgentSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UserHeartbeatJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId) {}

    public function handle(AIManager $aiManager): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $settings = AgentSettings::forUser($user);
        if (! $settings->heartbeatEnabled) {
            return;
        }

        $recentUnread = AgentReminder::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->where('created_at', '>', now()->subDay())
            ->count();

        if ($recentUnread >= 3) {
            return;
        }

        $memories = UserMemory::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(18)
            ->pluck('content')
            ->filter()
            ->values();

        if ($memories->isEmpty()) {
            return;
        }

        $list = $memories->map(fn (string $c, int $i) => ($i + 1).'. '.$c)->implode("\n");

        $messages = [
            [
                'role' => 'system',
                'content' => 'You generate at most 2 short proactive reminders for a user based on their stored memories (follow-ups, habits, deadlines they mentioned). '
                    .'Reply ONLY JSON: {"reminders":[{"title":"...","body":"..."}]}. Use empty array if nothing actionable. Keep title under 80 chars, body under 240.',
            ],
            [
                'role' => 'user',
                'content' => "Memories:\n{$list}",
            ],
        ];

        try {
            $result = $aiManager->chat($messages, $settings->heartbeatModel, ['temperature' => 0.4, 'max_tokens' => 400]);
        } catch (\Throwable $e) {
            Log::warning('heartbeat_ai_failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return;
        }

        $raw = trim($result['content'] ?? '');
        $parsed = json_decode($raw, true);
        if (! is_array($parsed) || ! isset($parsed['reminders']) || ! is_array($parsed['reminders'])) {
            return;
        }

        foreach (array_slice($parsed['reminders'], 0, 2) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = isset($row['title']) && is_string($row['title']) ? trim($row['title']) : '';
            if ($title === '') {
                continue;
            }
            $body = isset($row['body']) && is_string($row['body']) ? trim($row['body']) : null;

            AgentReminder::create([
                'user_id' => $user->id,
                'title' => mb_substr($title, 0, 200),
                'body' => $body !== null && $body !== '' ? mb_substr($body, 0, 2000) : null,
            ]);
        }
    }
}
