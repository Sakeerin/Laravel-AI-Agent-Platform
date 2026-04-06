<?php

namespace App\Services\Channels;

use Illuminate\Support\Facades\Http;

class SlackWebClient
{
    private const MAX_TEXT_LENGTH = 39000;

    public function postMessage(string $botToken, string $channel, string $text, ?string $threadTs = null): void
    {
        $payload = [
            'channel' => $channel,
            'text' => $this->truncate($text, self::MAX_TEXT_LENGTH),
        ];

        if ($threadTs !== null && $threadTs !== '') {
            $payload['thread_ts'] = $threadTs;
        }

        $response = Http::withToken($botToken)
            ->acceptJson()
            ->asJson()
            ->post('https://slack.com/api/chat.postMessage', $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        $json = $response->json();
        if (! ($json['ok'] ?? false)) {
            throw new \RuntimeException('slack.chat.postMessage: '.($json['error'] ?? 'unknown'));
        }
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1).'…';
    }
}
