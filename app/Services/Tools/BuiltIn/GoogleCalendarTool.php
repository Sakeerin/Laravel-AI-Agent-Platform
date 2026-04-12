<?php

namespace App\Services\Tools\BuiltIn;

use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;
use Illuminate\Support\Facades\Http;

class GoogleCalendarTool extends BaseTool
{
    public function name(): string
    {
        return 'google_calendar';
    }

    public function displayName(): string
    {
        return 'Google Calendar';
    }

    public function description(): string
    {
        return 'List upcoming events from Google Calendar when GOOGLE_CALENDAR_ACCESS_TOKEN is configured in .env. Otherwise returns setup instructions.';
    }

    public function category(): string
    {
        return 'productivity';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'max_results' => [
                    'type' => 'integer',
                    'description' => 'Max events to return (default 10)',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments, ?ToolContext $context = null): array
    {
        $token = config('services.integrations.google_calendar_access_token');
        if (! is_string($token) || trim($token) === '') {
            return [
                'success' => true,
                'result' => 'Google Calendar is not connected. Set GOOGLE_CALENDAR_ACCESS_TOKEN in .env (OAuth access token with https://www.googleapis.com/auth/calendar.readonly) and restart the app.',
            ];
        }

        $max = min(50, max(1, (int) ($arguments['max_results'] ?? 10)));

        $response = Http::timeout(20)
            ->withToken($token)
            ->get('https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                'maxResults' => $max,
                'singleEvents' => 'true',
                'orderBy' => 'startTime',
                'timeMin' => now()->toIso8601String(),
            ]);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'Google Calendar API error: '.$response->status().' '.$response->body(),
            ];
        }

        $items = $response->json('items') ?? [];
        if ($items === []) {
            return ['success' => true, 'result' => 'No upcoming events found.'];
        }

        $lines = [];
        foreach ($items as $ev) {
            $start = $ev['start']['dateTime'] ?? $ev['start']['date'] ?? '?';
            $lines[] = ($ev['summary'] ?? '(no title)').' — '.$start;
        }

        return ['success' => true, 'result' => implode("\n", $lines)];
    }
}
