<?php

namespace App\Services\Tools\BuiltIn;

use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;
use Illuminate\Support\Facades\Http;

class StockQuoteTool extends BaseTool
{
    public function name(): string
    {
        return 'stock_quote';
    }

    public function displayName(): string
    {
        return 'Stock quote';
    }

    public function description(): string
    {
        return 'Get a recent price quote for a stock or ETF symbol (e.g. AAPL, MSFT, SPY). Uses public market data.';
    }

    public function category(): string
    {
        return 'finance';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'symbol' => [
                    'type' => 'string',
                    'description' => 'Ticker symbol, e.g. AAPL',
                ],
            ],
            'required' => ['symbol'],
        ];
    }

    public function execute(array $arguments, ?ToolContext $context = null): array
    {
        $raw = strtoupper(preg_replace('/[^A-Za-z0-9\.\-]/', '', (string) ($arguments['symbol'] ?? '')));
        if ($raw === '') {
            return ['success' => false, 'error' => 'symbol is required'];
        }

        $url = 'https://query1.finance.yahoo.com/v8/finance/chart/'.$raw;
        $response = Http::timeout(15)
            ->withHeaders([
                'User-Agent' => 'LaravelAIAgent/1.0',
                'Accept' => 'application/json',
            ])
            ->get($url, [
                'range' => '1d',
                'interval' => '1m',
            ]);

        if (! $response->successful()) {
            return ['success' => false, 'error' => 'Failed to fetch quote for '.$raw];
        }

        $meta = $response->json('chart.result.0.meta');
        if (! is_array($meta)) {
            return ['success' => false, 'error' => 'No data for symbol '.$raw];
        }

        $price = $meta['regularMarketPrice'] ?? $meta['previousClose'] ?? null;
        $cur = $meta['currency'] ?? '';
        $name = $meta['longName'] ?? $meta['shortName'] ?? $raw;
        $prev = $meta['previousClose'] ?? null;

        $line = "{$name} ({$raw})";
        if ($price !== null) {
            $line .= ': '.$price.' '.$cur;
        }
        if (is_numeric($price) && is_numeric($prev) && (float) $prev != 0.0) {
            $pct = round(((float) $price - (float) $prev) / (float) $prev * 100, 2);
            $line .= " (prev close {$prev}, change {$pct}%)";
        }

        return ['success' => true, 'result' => $line];
    }
}
