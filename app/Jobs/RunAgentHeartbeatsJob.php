<?php

namespace App\Jobs;

use App\Models\User;
use App\Support\AgentSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunAgentHeartbeatsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        User::query()
            ->whereNotNull('agent_settings')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    if (AgentSettings::forUser($user)->heartbeatEnabled) {
                        UserHeartbeatJob::dispatch($user->id);
                    }
                }
            });
    }
}
