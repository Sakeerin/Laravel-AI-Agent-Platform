<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ChannelConnection;
use App\Services\Channels\ChannelChatService;
use App\Services\Channels\ChannelOutboundRouter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class LineWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $webhook_key,
        ChannelChatService $channelChat,
        ChannelOutboundRouter $outbound,
    ): Response {
        $connection = ChannelConnection::query()
            ->where('webhook_key', $webhook_key)
            ->where('provider', 'line')
            ->where('is_enabled', true)
            ->first();

        if (! $connection) {
            return response('Not Found', 404);
        }

        $secret = $connection->lineChannelSecret();
        $body = $request->getContent();
        $signature = $request->header('X-Line-Signature');

        if (! $secret || ! $signature) {
            return response('Unauthorized', 401);
        }

        $expected = base64_encode(hash_hmac('sha256', $body, $secret, true));
        if (! hash_equals($expected, $signature)) {
            return response('Unauthorized', 401);
        }

        $payload = json_decode($body, true);
        if (! is_array($payload) || ! isset($payload['events'])) {
            return response('Bad Request', 400);
        }

        $token = $connection->lineChannelAccessToken();
        if (! $token) {
            return response('Not Found', 404);
        }

        foreach ($payload['events'] as $event) {
            if (($event['type'] ?? '') !== 'message') {
                continue;
            }
            if (($event['message']['type'] ?? '') !== 'text') {
                continue;
            }
            $replyToken = $event['replyToken'] ?? null;
            $sourceId = $event['source']['userId'] ?? $event['source']['groupId'] ?? $event['source']['roomId'] ?? null;
            $text = $event['message']['text'] ?? '';
            if (! $replyToken || ! $sourceId || $text === '') {
                continue;
            }

            $rateKey = 'channel-inbound-msg:'.$connection->id.':'.$sourceId;
            if (RateLimiter::tooManyAttempts($rateKey, 30)) {
                continue;
            }
            RateLimiter::hit($rateKey, 60);

            try {
                $reply = $channelChat->replyToText($connection, (string) $sourceId, $text);
                $outbound->lineReply($token, $replyToken, $reply);
            } catch (\Throwable $e) {
                Log::error('line_webhook_chat_failed', [
                    'connection_id' => $connection->id,
                    'exception' => $e->getMessage(),
                ]);
                try {
                    $outbound->lineReply($token, $replyToken, 'Sorry, something went wrong. Please try again.');
                } catch (\Throwable) {
                    // ignore secondary failures
                }
            }
        }

        return response('OK', 200);
    }
}
