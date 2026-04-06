<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChannelConnectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->channelConnections()->orderBy('provider')->get();

        return response()->json($items->map(fn (ChannelConnection $c) => $this->summary($c)));
    }

    public function show(Request $request, ChannelConnection $channelConnection): JsonResponse
    {
        abort_if($channelConnection->user_id !== $request->user()->id, 403);

        return response()->json($this->detail($channelConnection));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', Rule::in(['line', 'telegram', 'slack', 'discord'])],
            'label' => ['nullable', 'string', 'max:100'],
            'line_channel_secret' => ['required_if:provider,line', 'nullable', 'string'],
            'line_channel_access_token' => ['required_if:provider,line', 'nullable', 'string'],
            'telegram_bot_token' => ['required_if:provider,telegram', 'nullable', 'string'],
            'slack_signing_secret' => ['required_if:provider,slack', 'nullable', 'string'],
            'slack_bot_token' => ['required_if:provider,slack', 'nullable', 'string'],
            'discord_public_key' => ['required_if:provider,discord', 'nullable', 'string'],
            'discord_application_id' => ['required_if:provider,discord', 'nullable', 'string'],
            'discord_bot_token' => ['nullable', 'string'],
        ]);

        if ($request->user()->channelConnections()->where('provider', $validated['provider'])->exists()) {
            return response()->json([
                'message' => 'A connection for this provider already exists.',
            ], 422);
        }

        $credentials = match ($validated['provider']) {
            'line' => [
                'channel_secret' => $validated['line_channel_secret'],
                'channel_access_token' => $validated['line_channel_access_token'],
            ],
            'telegram' => [
                'bot_token' => $validated['telegram_bot_token'],
                'webhook_secret' => Str::random(64),
            ],
            'slack' => [
                'signing_secret' => $validated['slack_signing_secret'],
                'bot_token' => $validated['slack_bot_token'],
            ],
            'discord' => [
                'public_key' => $validated['discord_public_key'],
                'application_id' => $validated['discord_application_id'],
                'bot_token' => $validated['discord_bot_token'] ?? null,
            ],
            default => [],
        };

        $connection = $request->user()->channelConnections()->create([
            'provider' => $validated['provider'],
            'label' => $validated['label'] ?? null,
            'credentials' => $credentials,
            'is_enabled' => true,
        ]);

        return response()->json($this->detail($connection), 201);
    }

    public function update(Request $request, ChannelConnection $channelConnection): JsonResponse
    {
        abort_if($channelConnection->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'label' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_enabled' => ['sometimes', 'boolean'],
            'line_channel_secret' => ['sometimes', 'string'],
            'line_channel_access_token' => ['sometimes', 'string'],
            'telegram_bot_token' => ['sometimes', 'string'],
            'slack_signing_secret' => ['sometimes', 'string'],
            'slack_bot_token' => ['sometimes', 'string'],
            'discord_public_key' => ['sometimes', 'string'],
            'discord_application_id' => ['sometimes', 'string'],
            'discord_bot_token' => ['sometimes', 'nullable', 'string'],
        ]);

        if (array_key_exists('label', $validated)) {
            $channelConnection->label = $validated['label'];
        }
        if (array_key_exists('is_enabled', $validated)) {
            $channelConnection->is_enabled = $validated['is_enabled'];
        }

        $cred = $channelConnection->credentials ?? [];
        $credChanged = false;

        if ($channelConnection->provider === 'line') {
            if (isset($validated['line_channel_secret'])) {
                $cred['channel_secret'] = $validated['line_channel_secret'];
                $credChanged = true;
            }
            if (isset($validated['line_channel_access_token'])) {
                $cred['channel_access_token'] = $validated['line_channel_access_token'];
                $credChanged = true;
            }
        }

        if ($channelConnection->provider === 'telegram' && isset($validated['telegram_bot_token'])) {
            $cred['bot_token'] = $validated['telegram_bot_token'];
            if (empty($cred['webhook_secret'])) {
                $cred['webhook_secret'] = Str::random(64);
            }
            $credChanged = true;
        }

        if ($channelConnection->provider === 'slack') {
            if (isset($validated['slack_signing_secret'])) {
                $cred['signing_secret'] = $validated['slack_signing_secret'];
                $credChanged = true;
            }
            if (isset($validated['slack_bot_token'])) {
                $cred['bot_token'] = $validated['slack_bot_token'];
                $credChanged = true;
            }
        }

        if ($channelConnection->provider === 'discord') {
            if (isset($validated['discord_public_key'])) {
                $cred['public_key'] = $validated['discord_public_key'];
                $credChanged = true;
            }
            if (isset($validated['discord_application_id'])) {
                $cred['application_id'] = $validated['discord_application_id'];
                $credChanged = true;
            }
            if (array_key_exists('discord_bot_token', $validated)) {
                $cred['bot_token'] = $validated['discord_bot_token'];
                $credChanged = true;
            }
        }

        if ($credChanged) {
            $channelConnection->credentials = $cred;
        }

        $channelConnection->save();

        return response()->json($this->detail($channelConnection));
    }

    public function destroy(Request $request, ChannelConnection $channelConnection): JsonResponse
    {
        abort_if($channelConnection->user_id !== $request->user()->id, 403);

        $channelConnection->delete();

        return response()->json(null, 204);
    }

    public function registerTelegramWebhook(Request $request, ChannelConnection $channelConnection): JsonResponse
    {
        abort_if($channelConnection->user_id !== $request->user()->id, 403);

        if ($channelConnection->provider !== 'telegram') {
            return response()->json(['message' => 'Not a Telegram connection.'], 422);
        }

        $token = $channelConnection->telegramBotToken();
        if (! $token) {
            return response()->json(['message' => 'Bot token is missing.'], 422);
        }

        $cred = $channelConnection->credentials ?? [];
        if (empty($cred['webhook_secret'])) {
            $cred['webhook_secret'] = Str::random(64);
            $channelConnection->credentials = $cred;
            $channelConnection->save();
        }

        $secret = $channelConnection->telegramWebhookSecret();
        $url = $channelConnection->webhookUrl();

        $response = Http::asJson()->post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message'],
        ]);

        $data = $response->json();

        if (! $response->ok() || empty($data['ok'])) {
            return response()->json([
                'message' => 'Telegram setWebhook failed.',
                'telegram' => $data,
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'webhook_url' => $url,
        ]);
    }

    private function summary(ChannelConnection $connection): array
    {
        return [
            'id' => $connection->id,
            'provider' => $connection->provider,
            'label' => $connection->label,
            'is_enabled' => $connection->is_enabled,
            'webhook_url' => $connection->webhookUrl(),
            'created_at' => $connection->created_at,
        ];
    }

    private function detail(ChannelConnection $connection): array
    {
        $base = $this->summary($connection);

        if ($connection->provider === 'telegram') {
            $base['telegram_webhook_secret'] = $connection->telegramWebhookSecret();
        }

        if ($connection->provider === 'line') {
            $base['line_channel_secret_set'] = (bool) $connection->lineChannelSecret();
            $base['line_channel_access_token_tail'] = $this->tailToken($connection->lineChannelAccessToken());
        }

        if ($connection->provider === 'slack') {
            $base['slack_signing_secret_set'] = (bool) $connection->slackSigningSecret();
            $base['slack_bot_token_tail'] = $this->tailToken($connection->slackBotToken());
        }

        if ($connection->provider === 'discord') {
            $base['discord_public_key_set'] = (bool) $connection->discordPublicKey();
            $base['discord_application_id'] = $connection->discordApplicationId();
            $base['discord_bot_token_tail'] = $this->tailToken($connection->discordBotToken());
        }

        return $base;
    }

    private function tailToken(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return '…'.substr($value, -6);
    }
}
