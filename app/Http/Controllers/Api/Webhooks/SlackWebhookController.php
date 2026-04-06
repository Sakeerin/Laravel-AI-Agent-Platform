<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ChannelConnection;
use App\Services\Channels\ChannelChatService;
use App\Services\Channels\ChannelOutboundRouter;
use App\Services\Channels\SlackRequestValidator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SlackWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $webhook_key,
        ChannelChatService $channelChat,
        ChannelOutboundRouter $outbound,
        SlackRequestValidator $slackVerify,
    ): Response {
        $connection = ChannelConnection::query()
            ->where('webhook_key', $webhook_key)
            ->where('provider', 'slack')
            ->where('is_enabled', true)
            ->first();

        if (! $connection) {
            return response('Not Found', 404);
        }

        $signingSecret = $connection->slackSigningSecret();
        if (! $signingSecret || ! $slackVerify->isValid($request, $signingSecret)) {
            return response('Unauthorized', 403);
        }

        $payload = json_decode($request->getContent(), true);
        if (! is_array($payload)) {
            return response('Bad Request', 400);
        }

        if (($payload['type'] ?? '') === 'url_verification') {
            return response($payload['challenge'] ?? '', 200)
                ->header('Content-Type', 'text/plain');
        }

        if (($payload['type'] ?? '') !== 'event_callback') {
            return response('', 200);
        }

        $event = $payload['event'] ?? [];
        if (! is_array($event) || ($event['type'] ?? '') !== 'message') {
            return response('', 200);
        }

        if (isset($event['bot_id']) || isset($event['subtype'])) {
            return response('', 200);
        }

        $text = $this->normalizeSlackText((string) ($event['text'] ?? ''));
        $user = $event['user'] ?? null;
        $channel = $event['channel'] ?? null;

        if ($text === '' || ! is_string($user) || ! is_string($channel)) {
            return response('', 200);
        }

        $threadTs = $event['thread_ts'] ?? $event['ts'] ?? null;

        $externalId = $channel.':u:'.$user;

        $rateKey = 'channel-inbound-msg:'.$connection->id.':'.$externalId;
        if (RateLimiter::tooManyAttempts($rateKey, 30)) {
            return response('', 200);
        }
        RateLimiter::hit($rateKey, 60);

        $botToken = $connection->slackBotToken();
        if (! $botToken) {
            return response('', 200);
        }

        try {
            $reply = $channelChat->replyToText($connection, $externalId, $text);
            $outbound->slackMessage($botToken, $channel, $reply, is_string($threadTs) ? $threadTs : null);
        } catch (\Throwable $e) {
            Log::error('slack_webhook_chat_failed', [
                'connection_id' => $connection->id,
                'exception' => $e->getMessage(),
            ]);
            try {
                $outbound->slackMessage(
                    $botToken,
                    $channel,
                    'Sorry, something went wrong. Please try again.',
                    is_string($threadTs) ? $threadTs : null
                );
            } catch (\Throwable) {
                // ignore
            }
        }

        return response('', 200);
    }

    private function normalizeSlackText(string $text): string
    {
        $stripped = preg_replace('/<@[^>]+>\s*/u', '', $text);

        return trim($stripped ?? '');
    }
}
