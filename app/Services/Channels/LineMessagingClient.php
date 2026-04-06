<?php

namespace App\Services\Channels;

use Illuminate\Support\Facades\Http;

class LineMessagingClient
{
    private const MAX_TEXT_LENGTH = 5000;

    public function reply(string $accessToken, string $replyToken, string $text): void
    {
        $body = [
            'replyToken' => $replyToken,
            'messages' => [
                ['type' => 'text', 'text' => $this->truncate($text, self::MAX_TEXT_LENGTH)],
            ],
        ];

        Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->post('https://api.line.me/v2/bot/message/reply', $body)
            ->throw();
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1).'…';
    }
}
