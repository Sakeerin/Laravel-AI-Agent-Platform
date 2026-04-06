<?php

namespace App\Services\Channels;

use Illuminate\Support\Facades\Http;

class TelegramBotClient
{
    private const MAX_TEXT_LENGTH = 4096;

    public function sendMessage(string $botToken, int|string $chatId, string $text): void
    {
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $payload = [
            'chat_id' => $chatId,
            'text' => $this->truncate($text, self::MAX_TEXT_LENGTH),
        ];

        Http::asJson()->post($url, $payload)->throw();
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1).'…';
    }
}
