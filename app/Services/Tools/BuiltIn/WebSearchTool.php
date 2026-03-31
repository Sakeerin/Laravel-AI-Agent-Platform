<?php

namespace App\Services\Tools\BuiltIn;

use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;
use Illuminate\Support\Facades\Http;

class WebSearchTool extends BaseTool
{
    public function name(): string
    {
        return 'web_search';
    }

    public function displayName(): string
    {
        return 'Web Search';
    }

    public function description(): string
    {
        return 'Search the web for current information using a search query. Returns relevant web results with titles, URLs, and descriptions.';
    }

    public function category(): string
    {
        return 'search';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The search query to look up',
                ],
                'num_results' => [
                    'type' => 'integer',
                    'description' => 'Number of results to return (max 10)',
                    'default' => 5,
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(array $arguments, ?ToolContext $context = null): array
    {
        $query = $arguments['query'] ?? '';
        $numResults = min($arguments['num_results'] ?? 5, 10);

        if (empty($query)) {
            return ['success' => false, 'result' => null, 'error' => 'Search query is required'];
        }

        $braveKey = config('services.brave.api_key');
        if ($braveKey) {
            return $this->searchBrave($query, $numResults, $braveKey);
        }

        $googleKey = config('services.google.search_api_key');
        $googleCx = config('services.google.search_cx');
        if ($googleKey && $googleCx) {
            return $this->searchGoogle($query, $numResults, $googleKey, $googleCx);
        }

        return $this->searchDuckDuckGo($query, $numResults);
    }

    private function searchBrave(string $query, int $numResults, string $apiKey): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip',
                'X-Subscription-Token' => $apiKey,
            ])->timeout(15)->get('https://api.search.brave.com/res/v1/web/search', [
                'q' => $query,
                'count' => $numResults,
            ]);

            $response->throw();
            $data = $response->json();

            $results = [];
            foreach ($data['web']['results'] ?? [] as $item) {
                $results[] = [
                    'title' => $item['title'] ?? '',
                    'url' => $item['url'] ?? '',
                    'description' => $item['description'] ?? '',
                ];
            }

            return [
                'success' => true,
                'result' => [
                    'query' => $query,
                    'source' => 'brave',
                    'results' => $results,
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'result' => null, 'error' => 'Brave search failed: ' . $e->getMessage()];
        }
    }

    private function searchGoogle(string $query, int $numResults, string $apiKey, string $cx): array
    {
        try {
            $response = Http::timeout(15)->get('https://www.googleapis.com/customsearch/v1', [
                'key' => $apiKey,
                'cx' => $cx,
                'q' => $query,
                'num' => $numResults,
            ]);

            $response->throw();
            $data = $response->json();

            $results = [];
            foreach ($data['items'] ?? [] as $item) {
                $results[] = [
                    'title' => $item['title'] ?? '',
                    'url' => $item['link'] ?? '',
                    'description' => $item['snippet'] ?? '',
                ];
            }

            return [
                'success' => true,
                'result' => [
                    'query' => $query,
                    'source' => 'google',
                    'results' => $results,
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'result' => null, 'error' => 'Google search failed: ' . $e->getMessage()];
        }
    }

    private function searchDuckDuckGo(string $query, int $numResults): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'AI-Agent-Platform/1.0'])
                ->get('https://api.duckduckgo.com/', [
                    'q' => $query,
                    'format' => 'json',
                    'no_redirect' => 1,
                    'no_html' => 1,
                ]);

            $data = $response->json();
            $results = [];

            if (!empty($data['Abstract'])) {
                $results[] = [
                    'title' => $data['Heading'] ?? $query,
                    'url' => $data['AbstractURL'] ?? '',
                    'description' => $data['Abstract'],
                ];
            }

            foreach ($data['RelatedTopics'] ?? [] as $topic) {
                if (isset($topic['Text']) && count($results) < $numResults) {
                    $results[] = [
                        'title' => $topic['Text'],
                        'url' => $topic['FirstURL'] ?? '',
                        'description' => $topic['Text'],
                    ];
                }
            }

            return [
                'success' => true,
                'result' => [
                    'query' => $query,
                    'source' => 'duckduckgo',
                    'results' => $results,
                    'note' => empty($results)
                        ? 'No results from DuckDuckGo instant answers. Configure BRAVE_API_KEY or GOOGLE_SEARCH_API_KEY for better results.'
                        : null,
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'result' => null, 'error' => 'DuckDuckGo search failed: ' . $e->getMessage()];
        }
    }
}
