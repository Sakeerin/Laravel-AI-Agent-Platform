<?php

namespace App\Services\Tools\BuiltIn;

use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;
use Illuminate\Support\Facades\Http;

class NotionTool extends BaseTool
{
    public function name(): string
    {
        return 'notion_search';
    }

    public function displayName(): string
    {
        return 'Notion search';
    }

    public function description(): string
    {
        return 'Search your Notion workspace when NOTION_INTEGRATION_TOKEN is configured.';
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
                    'description' => 'Text to search for across pages and databases',
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(array $arguments, ?ToolContext $context = null): array
    {
        $token = config('services.integrations.notion_token');
        if (! is_string($token) || trim($token) === '') {
            return [
                'success' => true,
                'result' => 'Notion is not connected. Create an internal integration at https://www.notion.so/my-integrations and set NOTION_INTEGRATION_TOKEN in .env.',
            ];
        }

        $query = (string) ($arguments['query'] ?? '');
        $response = Http::timeout(20)
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Notion-Version' => '2022-06-28',
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.notion.com/v1/search', [
                'query' => $query,
                'page_size' => 10,
            ]);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'Notion API error: '.$response->status().' '.$response->body(),
            ];
        }

        $results = $response->json('results') ?? [];
        if ($results === []) {
            return ['success' => true, 'result' => 'No Notion pages matched.'];
        }

        $lines = [];
        foreach ($results as $r) {
            $title = '';
            if (($r['object'] ?? '') === 'page') {
                $props = $r['properties'] ?? [];
                foreach ($props as $prop) {
                    if (($prop['type'] ?? '') === 'title' && ! empty($prop['title'][0]['plain_text'])) {
                        $title = $prop['title'][0]['plain_text'];
                        break;
                    }
                }
            }
            $url = $r['url'] ?? '';
            $lines[] = ($title !== '' ? $title : ($r['id'] ?? 'item')).' — '.$url;
        }

        return ['success' => true, 'result' => implode("\n", $lines)];
    }
}
