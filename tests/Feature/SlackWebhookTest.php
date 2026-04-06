<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlackWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_slack_url_verification_returns_challenge(): void
    {
        $user = User::factory()->create();
        $secret = 'slack-signing-secret';
        $connection = $user->channelConnections()->create([
            'provider' => 'slack',
            'credentials' => [
                'signing_secret' => $secret,
                'bot_token' => 'xoxb-test',
            ],
            'is_enabled' => true,
        ]);

        $body = json_encode([
            'type' => 'url_verification',
            'challenge' => 'challenge-token-123',
        ]);

        $timestamp = (string) time();
        $baseline = 'v0:'.$timestamp.':'.$body;
        $sig = 'v0='.hash_hmac('sha256', $baseline, $secret, false);

        $response = $this->call(
            'POST',
            "/api/webhooks/slack/{$connection->webhook_key}",
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SLACK_REQUEST_TIMESTAMP' => $timestamp,
                'HTTP_X_SLACK_SIGNATURE' => $sig,
            ],
            $body,
        );

        $response->assertOk();
        $this->assertSame('challenge-token-123', $response->getContent());
    }
}
