<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ChannelConnection;
use App\Services\Channels\ChannelChatService;
use App\Services\Channels\ChannelOutboundRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $webhook_key,
        ChannelChatService $channelChat,
        ChannelOutboundRouter $outbound,
    ): JsonResponse {
        $connection = ChannelConnection::query()
            ->where('webhook_key', $webhook_key)
            ->where('provider', 'telegram')
            ->where('is_enabled', true)
            ->first();

        if (! $connection) {
            return response()->json(['ok' => true]);
        }

        $secret = $connection->telegramWebhookSecret();
        $header = $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($secret !== null && $secret !== '' && (! is_string($header) || ! hash_equals($secret, $header))) {
            return response()->json(['ok' => false], 403);
        }

        $payload = $request->all();
        $message = $payload['message'] ?? null;
        if (! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $chatId = $message['chat']['id'] ?? null;
        $rawText = $message['text'] ?? '';
        if ($chatId === null) {
            return response()->json(['ok' => true]);
        }

        $text = trim((string) $rawText);
        if ($text === '') {
            return response()->json(['ok' => true]);
        }

        $rateKey = 'channel-inbound-msg:'.$connection->id.':'.$chatId;
        if (RateLimiter::tooManyAttempts($rateKey, 30)) {
            return response()->json(['ok' => true]);
        }
        RateLimiter::hit($rateKey, 60);

        $botToken = $connection->telegramBotToken();
        if (! $botToken) {
            return response()->json(['ok' => true]);
        }

        try {
            $reply = $channelChat->replyToText($connection, (string) $chatId, $text);
            $outbound->telegramMessage($botToken, $chatId, $reply);
        } catch (\Throwable $e) {
            Log::error('telegram_webhook_chat_failed', [
                'connection_id' => $connection->id,
                'exception' => $e->getMessage(),
            ]);
            try {
                $outbound->telegramMessage($botToken, $chatId, 'Sorry, something went wrong. Please try again.');
            } catch (\Throwable) {
                // ignore
            }
        }

        return response()->json(['ok' => true]);
    }
}
