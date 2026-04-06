<?php

namespace App\Services\Channels;

/**
 * Single entry for sending outbound messages to messengers (channel router).
 */
class ChannelOutboundRouter
{
    public function __construct(
        private readonly LineMessagingClient $line,
        private readonly TelegramBotClient $telegram,
        private readonly SlackWebClient $slack,
        private readonly DiscordInteractionClient $discord,
    ) {}

    public function lineReply(string $accessToken, string $replyToken, string $text): void
    {
        $this->line->reply($accessToken, $replyToken, $text);
    }

    public function telegramMessage(string $botToken, int|string $chatId, string $text): void
    {
        $this->telegram->sendMessage($botToken, $chatId, $text);
    }

    public function slackMessage(string $botToken, string $channel, string $text, ?string $threadTs): void
    {
        $this->slack->postMessage($botToken, $channel, $text, $threadTs);
    }

    public function discordEditOriginal(string $applicationId, string $interactionToken, string $text): void
    {
        $this->discord->editOriginalResponse($applicationId, $interactionToken, $text);
    }
}
