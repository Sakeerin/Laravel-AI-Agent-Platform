<?php

namespace App\Services\Tools\BuiltIn;

use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class BrowserTool extends BaseTool
{
    public function name(): string
    {
        return 'browser';
    }

    public function displayName(): string
    {
        return 'Browser';
    }

    public function description(): string
    {
        return 'Fetch and read web page content. Can retrieve the text content of any URL, useful for reading articles, documentation, or extracting data from websites.';
    }

    public function category(): string
    {
        return 'web';
    }

    public function timeoutSeconds(): int
    {
        return 30;
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'description' => 'The URL to fetch and read',
                ],
                'action' => [
                    'type' => 'string',
                    'enum' => ['fetch', 'screenshot'],
                    'description' => 'Action to perform: fetch page content or take screenshot',
                    'default' => 'fetch',
                ],
                'selector' => [
                    'type' => 'string',
                    'description' => 'Optional CSS selector to extract specific content from the page',
                ],
            ],
            'required' => ['url'],
        ];
    }

    public function execute(array $arguments, ?ToolContext $context = null): array
    {
        $url = $arguments['url'] ?? '';
        $action = $arguments['action'] ?? 'fetch';

        if (empty($url)) {
            return ['success' => false, 'result' => null, 'error' => 'URL is required'];
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'result' => null, 'error' => 'Invalid URL format'];
        }

        if ($this->isBlockedUrl($url)) {
            return ['success' => false, 'result' => null, 'error' => 'URL is blocked for security reasons'];
        }

        return match ($action) {
            'fetch' => $this->fetchPage($url, $arguments['selector'] ?? null),
            'screenshot' => $this->takeScreenshot($url, $context),
            default => ['success' => false, 'result' => null, 'error' => "Unknown action: {$action}"],
        };
    }

    private function fetchPage(string $url, ?string $selector): array
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; AI-Agent-Platform/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($url);

            $response->throw();

            $html = $response->body();
            $text = $this->htmlToText($html, $selector);

            $maxLen = 30000;
            if (strlen($text) > $maxLen) {
                $text = substr($text, 0, $maxLen) . "\n\n... (content truncated)";
            }

            return [
                'success' => true,
                'result' => [
                    'url' => $url,
                    'status' => $response->status(),
                    'content' => $text,
                    'content_length' => strlen($text),
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'result' => null, 'error' => 'Failed to fetch page: ' . $e->getMessage()];
        }
    }

    private function takeScreenshot(string $url, ?ToolContext $context): array
    {
        if (! config('services.browser.playwright_enabled')) {
            return [
                'success' => false,
                'result' => null,
                'error' => 'Screenshots are disabled. Set BROWSER_PLAYWRIGHT_ENABLED=true, install Node on the server, run `npx playwright install chromium`, then retry. Or use action=fetch to read page text.',
            ];
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pwshot');
        if ($tmp === false) {
            return ['success' => false, 'result' => null, 'error' => 'Could not create temp file for screenshot'];
        }

        $pngPath = $tmp.'.png';
        @unlink($tmp);

        $timeout = (int) config('services.browser.playwright_timeout', 45);
        $viewport = (string) config('services.browser.viewport', '1280,720');
        $npx = (string) config('services.browser.npx_binary', 'npx');

        $command = [$npx, '--yes', 'playwright', 'screenshot', $url, $pngPath, '--viewport-size='.$viewport];

        try {
            $process = new Process($command);
            $process->setTimeout($timeout);
            $process->run();

            if (! $process->isSuccessful() || ! is_readable($pngPath)) {
                @unlink($pngPath);

                return [
                    'success' => false,
                    'result' => null,
                    'error' => 'Playwright screenshot failed: '.trim($process->getErrorOutput() ?: $process->getOutput() ?: 'unknown error'),
                ];
            }

            $raw = file_get_contents($pngPath);
            @unlink($pngPath);

            if ($raw === false) {
                return ['success' => false, 'result' => null, 'error' => 'Could not read screenshot file'];
            }

            $maxBytes = 1_800_000;
            if (strlen($raw) > $maxBytes) {
                return [
                    'success' => false,
                    'result' => null,
                    'error' => 'Screenshot exceeds maximum size ('.$maxBytes.' bytes). Try a simpler page or increase limit in BrowserTool.',
                ];
            }

            return [
                'success' => true,
                'result' => [
                    'url' => $url,
                    'format' => 'png',
                    'image_base64' => base64_encode($raw),
                    'bytes' => strlen($raw),
                ],
            ];
        } catch (\Exception $e) {
            @unlink($pngPath);

            return ['success' => false, 'result' => null, 'error' => 'Playwright screenshot error: '.$e->getMessage()];
        }
    }

    private function htmlToText(string $html, ?string $selector): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<nav\b[^>]*>.*?<\/nav>/is', '', $html);
        $html = preg_replace('/<footer\b[^>]*>.*?<\/footer>/is', '', $html);
        $html = preg_replace('/<header\b[^>]*>.*?<\/header>/is', '', $html);

        $html = preg_replace('/<h([1-6])\b[^>]*>(.*?)<\/h[1-6]>/is', "\n\n$2\n", $html);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>/i', "\n\n", $html);
        $html = preg_replace('/<li\b[^>]*>/i', "\n• ", $html);

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private function isBlockedUrl(string $url): bool
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';

        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '[::]', '[::1]'];
        foreach ($blockedHosts as $blocked) {
            if (strcasecmp($host, $blocked) === 0) {
                return true;
            }
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
            && filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        return false;
    }
}
