<?php

namespace Database\Seeders;

use App\Models\SkillPackage;
use Illuminate\Database\Seeder;

class SkillPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'slug' => 'weather-open-meteo',
                'title' => 'Weather (Open-Meteo)',
                'description' => 'Current conditions and short forecast by city or coordinates. No API key required.',
                'category' => 'lifestyle',
                'version' => '1.0.0',
                'tags' => ['weather', 'forecast', 'free'],
                'icon' => '🌤️',
                'is_premium' => false,
                'is_featured' => true,
                'manifest' => [
                    'schema_version' => 1,
                    'name' => 'weather_forecast',
                    'display_name' => 'Weather',
                    'description' => 'Get current weather or forecast by city name or latitude/longitude.',
                    'category' => 'lifestyle',
                    'parameters_schema' => [
                        'type' => 'object',
                        'properties' => [
                            'location' => ['type' => 'string', 'description' => 'City and optional country'],
                            'latitude' => ['type' => 'number'],
                            'longitude' => ['type' => 'number'],
                        ],
                        'required' => [],
                    ],
                    'execution' => [
                        'type' => 'native',
                        'tool_name' => 'weather_forecast',
                    ],
                ],
            ],
            [
                'slug' => 'stock-quote-yahoo',
                'title' => 'Stock quotes',
                'description' => 'Recent price for stocks and ETFs by ticker symbol.',
                'category' => 'finance',
                'version' => '1.0.0',
                'tags' => ['finance', 'stocks', 'markets'],
                'icon' => '📈',
                'is_premium' => false,
                'is_featured' => true,
                'manifest' => [
                    'schema_version' => 1,
                    'name' => 'stock_quote',
                    'display_name' => 'Stock quote',
                    'description' => 'Get a recent price quote for a stock or ETF symbol.',
                    'category' => 'finance',
                    'parameters_schema' => [
                        'type' => 'object',
                        'properties' => [
                            'symbol' => ['type' => 'string', 'description' => 'Ticker e.g. AAPL'],
                        ],
                        'required' => ['symbol'],
                    ],
                    'execution' => [
                        'type' => 'native',
                        'tool_name' => 'stock_quote',
                    ],
                ],
            ],
            [
                'slug' => 'google-calendar',
                'title' => 'Google Calendar',
                'description' => 'List upcoming events when a Google OAuth access token is configured.',
                'category' => 'productivity',
                'version' => '1.0.0',
                'tags' => ['google', 'calendar', 'oauth'],
                'icon' => '📅',
                'is_premium' => false,
                'is_featured' => false,
                'manifest' => [
                    'schema_version' => 1,
                    'name' => 'google_calendar',
                    'display_name' => 'Google Calendar',
                    'description' => 'List upcoming events from Google Calendar when configured.',
                    'category' => 'productivity',
                    'parameters_schema' => [
                        'type' => 'object',
                        'properties' => [
                            'max_results' => ['type' => 'integer', 'description' => 'Max events (default 10)'],
                        ],
                        'required' => [],
                    ],
                    'execution' => [
                        'type' => 'native',
                        'tool_name' => 'google_calendar',
                    ],
                ],
            ],
            [
                'slug' => 'gmail-search',
                'title' => 'Gmail search',
                'description' => 'Search mailbox snippets when Gmail OAuth token is configured.',
                'category' => 'productivity',
                'version' => '1.0.0',
                'tags' => ['google', 'email', 'gmail'],
                'icon' => '✉️',
                'is_premium' => false,
                'is_featured' => false,
                'manifest' => [
                    'schema_version' => 1,
                    'name' => 'gmail_search',
                    'display_name' => 'Gmail search',
                    'description' => 'Search Gmail when GMAIL_ACCESS_TOKEN is set.',
                    'category' => 'productivity',
                    'parameters_schema' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string'],
                            'max_results' => ['type' => 'integer'],
                        ],
                        'required' => ['query'],
                    ],
                    'execution' => [
                        'type' => 'native',
                        'tool_name' => 'gmail_search',
                    ],
                ],
            ],
            [
                'slug' => 'notion-search',
                'title' => 'Notion',
                'description' => 'Search pages and databases in your Notion workspace.',
                'category' => 'productivity',
                'version' => '1.0.0',
                'tags' => ['notion', 'notes', 'wiki'],
                'icon' => '📝',
                'is_premium' => false,
                'is_featured' => false,
                'manifest' => [
                    'schema_version' => 1,
                    'name' => 'notion_search',
                    'display_name' => 'Notion search',
                    'description' => 'Search Notion with NOTION_INTEGRATION_TOKEN.',
                    'category' => 'productivity',
                    'parameters_schema' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string'],
                        ],
                        'required' => ['query'],
                    ],
                    'execution' => [
                        'type' => 'native',
                        'tool_name' => 'notion_search',
                    ],
                ],
            ],
        ];

        foreach ($packages as $row) {
            SkillPackage::query()->updateOrCreate(
                ['slug' => $row['slug']],
                $row
            );
        }
    }
}
