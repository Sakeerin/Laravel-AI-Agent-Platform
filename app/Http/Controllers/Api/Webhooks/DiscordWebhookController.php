<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ChannelConnection;
use App\Services\Channels\ChannelChatService;
use App\Services\Channels\ChannelOutboundRouter;
use App\Services\Channels\DiscordInteractionVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class DiscordWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $webhook_key,
        ChannelChatService $channelChat,
        ChannelOutboundRouter $outbound,
        DiscordInteractionVerifier $verifier,
    ): JsonResponse {
        $connection = ChannelConnection::query()
            ->where('webhook_key', $webhook_key)
            ->where('provider', 'discord')
            ->where('is_enabled', true)
            ->first();

        if (! $connection) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $publicKey = $connection->discordPublicKey();
        if (! $publicKey || ! $verifier->isValid($request, $publicKey)) {
            return response()->json(['message' => 'invalid signature'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (! is_array($payload)) {
            return response()->json(['message' => 'invalid json'], 400);
        }

        $type = (int) ($payload['type'] ?? 0);

        if ($type === 1) {
            return response()->json(['type' => 1]);
        }

        if ($type !== 2) {
            return response()->json(['type' => 4, 'data' => ['content' => 'Unsupported interaction.', 'flags' => 64]]);
        }

        $data = $payload['data'] ?? [];
        if (! is_array($data)) {
            return response()->json(['type' => 4, 'data' => ['content' => 'Bad payload.', 'flags' => 64]]);
        }

        $prompt = $this->promptFromCommandOptions($data);
        if ($prompt === null || $prompt === '') {
            return response()->json([
                'type' => 4,
                'data' => [
                    'content' => 'Please provide text. Register a slash command with a string option (e.g. `/ask prompt:your message`).',
                    'flags' => 64,
                ],
            ]);
        }

        $guildId = $payload['guild_id'] ?? null;
        $userId = $payload['member']['user']['id'] ?? $payload['user']['id'] ?? null;
        $applicationId = (string) ($payload['application_id'] ?? '');
        $interactionToken = (string) ($payload['token'] ?? '');

        if (! is_string($userId) || $applicationId === '' || $interactionToken === '') {
            return response()->json(['type' => 4, 'data' => ['content' => 'Missing user context.', 'flags' => 64]]);
        }

        $externalId = (is_string($guildId) ? "g:{$guildId}:" : 'dm:').'u:'.$userId;

        $rateKey = 'channel-inbound-msg:'.$connection->id.':'.$externalId;
        if (RateLimiter::tooManyAttempts($rateKey, 20)) {
            return response()->json(['type' => 4, 'data' => ['content' => 'Rate limit: try again shortly.', 'flags' => 64]]);
        }
        RateLimiter::hit($rateKey, 60);

        $connectionId = $connection->id;

        dispatch(function () use ($connectionId, $externalId, $prompt, $applicationId, $interactionToken, $channelChat, $outbound): void {
            $conn = ChannelConnection::query()->find($connectionId);
            if (! $conn || ! $conn->is_enabled) {
                return;
            }

            try {
                $reply = $channelChat->replyToText($conn, $externalId, $prompt);
                $outbound->discordEditOriginal($applicationId, $interactionToken, $reply);
            } catch (\Throwable $e) {
                Log::error('discord_webhook_chat_failed', [
                    'connection_id' => $connectionId,
                    'exception' => $e->getMessage(),
                ]);
                try {
                    $outbound->discordEditOriginal(
                        $applicationId,
                        $interactionToken,
                        'Sorry, something went wrong. Please try again.'
                    );
                } catch (\Throwable) {
                    // ignore
                }
            }
        })->afterResponse();

        return response()->json(['type' => 5]);
    }

    private function promptFromCommandOptions(array $data): ?string
    {
        $options = $data['options'] ?? [];
        if (! is_array($options)) {
            return null;
        }

        foreach ($options as $opt) {
            if (! is_array($opt)) {
                continue;
            }
            if (($opt['type'] ?? null) === 3 && isset($opt['value']) && is_string($opt['value'])) {
                return trim($opt['value']);
            }
        }

        foreach ($options as $opt) {
            if (! is_array($opt)) {
                continue;
            }
            if (isset($opt['value']) && is_string($opt['value'])) {
                return trim($opt['value']);
            }
        }

        return null;
    }
}
