<?php

namespace App\Services\Tools\BuiltIn;

use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;
use Illuminate\Support\Facades\Http;

class GmailQueryTool extends BaseTool
{
    public function name(): string
    {
        return 'gmail_search';
    }

    public function displayName(): string
    {
        return 'Gmail search';
    }

    public function description(): string
    {
        return 'Search the user\'s Gmail when GMAIL_ACCESS_TOKEN is set (Gmail API read-only). Returns message snippets or setup instructions.';
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
                'query' => [
                    'type' => 'string',
                    'description' => 'Gmail search query (same syntax as Gmail search box)',
                ],
                'max_results' => [
                    'type' => 'integer',
                    'description' => 'Max messages (default 5, max 10)',
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(array $arguments, ?ToolContext $context = null): array
    {
        $token = config('services.integrations.gmail_access_token');
        if (! is_string($token) || trim($token) === '') {
            return [
                'success' => true,
                'result' => 'Gmail is not connected. Set GMAIL_ACCESS_TOKEN in .env (OAuth token with https://www.googleapis.com/auth/gmail.readonly).',
            ];
        }

        $q = (string) ($arguments['query'] ?? '');
        $max = min(10, max(1, (int) ($arguments['max_results'] ?? 5)));

        $list = Http::timeout(20)
            ->withToken($token)
            ->get('https://gmail.googleapis.com/gmail/v1/users/me/messages', [
                'q' => $q,
                'maxResults' => $max,
            ]);

        if (! $list->successful()) {
            return [
                'success' => false,
                'error' => 'Gmail API error: '.$list->status().' '.$list->body(),
            ];
        }

        $messages = $list->json('messages') ?? [];
        if ($messages === []) {
            return ['success' => true, 'result' => 'No messages matched your query.'];
        }

        $lines = [];
        foreach (array_slice($messages, 0, $max) as $m) {
            $id = $m['id'] ?? null;
            if (! $id) {
                continue;
            }
            $detail = Http::timeout(15)->withToken($token)->get(
                'https://gmail.googleapis.com/gmail/v1/users/me/messages/'.$id,
                ['format' => 'metadata', 'metadataHeaders' => ['Subject', 'From']]
            );
            if (! $detail->successful()) {
                continue;
            }
            $headers = $detail->json('payload.headers') ?? [];
            $subject = '';
            $from = '';
            foreach ($headers as $h) {
                if (($h['name'] ?? '') === 'Subject') {
                    $subject = $h['value'] ?? '';
                }
                if (($h['name'] ?? '') === 'From') {
                    $from = $h['value'] ?? '';
                }
            }
            $snippet = $detail->json('snippet') ?? '';
            $lines[] = trim($subject.' — '.$from)."\n  ".$snippet;
        }

        return ['success' => true, 'result' => implode("\n\n", $lines)];
    }
}
