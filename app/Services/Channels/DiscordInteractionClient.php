<?php

namespace App\Services\Channels;

use Illuminate\Support\Facades\Http;

class DiscordInteractionClient
{
    private const MAX_CONTENT_LENGTH = 2000;

    public function editOriginalResponse(string $applicationId, string $interactionToken, string $content): void
    {
        $url = "https://discord.com/api/v10/webhooks/{$applicationId}/{$interactionToken}/messages/@original";

        Http::patch($url, [
            'content' => $this->truncate($content, self::MAX_CONTENT_LENGTH),
        ])->throw();
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1).'…';
    }
}
