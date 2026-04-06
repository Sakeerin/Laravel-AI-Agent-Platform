<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LineWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_line_webhook_rejects_missing_signature(): void
    {
        $user = User::factory()->create();
        $connection = $user->channelConnections()->create([
            'provider' => 'line',
            'credentials' => [
                'channel_secret' => 'secret',
                'channel_access_token' => 'token',
            ],
            'is_enabled' => true,
        ]);

        $this->postJson("/api/webhooks/line/{$connection->webhook_key}", ['events' => []])
            ->assertUnauthorized();
    }

    public function test_line_webhook_accepts_empty_events_with_valid_signature(): void
    {
        $user = User::factory()->create();
        $secret = 'channel-secret-test';
        $connection = $user->channelConnections()->create([
            'provider' => 'line',
            'credentials' => [
                'channel_secret' => $secret,
                'channel_access_token' => 'token',
            ],
            'is_enabled' => true,
        ]);

        $body = json_encode(['events' => []]);
        $sig = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $this->call(
            'POST',
            "/api/webhooks/line/{$connection->webhook_key}",
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LINE_SIGNATURE' => $sig,
            ],
            $body,
        )->assertOk();
    }
}
