<?php

namespace App\Services\Tools\BuiltIn;

use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;
use Illuminate\Support\Facades\Http;

class WeatherTool extends BaseTool
{
    public function name(): string
    {
        return 'weather_forecast';
    }

    public function displayName(): string
    {
        return 'Weather';
    }

    public function description(): string
    {
        return 'Get current weather or forecast by city name or latitude/longitude. Uses Open-Meteo (no API key).';
    }

    public function category(): string
    {
        return 'lifestyle';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'location' => [
                    'type' => 'string',
                    'description' => 'City and optional country, e.g. "Bangkok" or "Paris, France"',
                ],
                'latitude' => [
                    'type' => 'number',
                    'description' => 'Latitude (use together with longitude if you already have coordinates)',
                ],
                'longitude' => [
                    'type' => 'number',
                    'description' => 'Longitude (use together with latitude)',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments, ?ToolContext $context = null): array
    {
        $lat = $arguments['latitude'] ?? null;
        $lon = $arguments['longitude'] ?? null;
        $location = trim((string) ($arguments['location'] ?? ''));

        if ((! is_numeric($lat) || ! is_numeric($lon)) && $location === '') {
            return [
                'success' => false,
                'error' => 'Provide either location (city name) or both latitude and longitude.',
            ];
        }

        if ($location !== '' && (! is_numeric($lat) || ! is_numeric($lon))) {
            $geo = Http::timeout(12)->get('https://geocoding-api.open-meteo.com/v1/search', [
                'name' => $location,
                'count' => 1,
            ]);

            if (! $geo->successful() || empty($geo->json('results.0'))) {
                return ['success' => false, 'error' => 'Could not geocode location: '.$location];
            }

            $first = $geo->json('results.0');
            $lat = $first['latitude'];
            $lon = $first['longitude'];
            $label = ($first['name'] ?? $location).', '.($first['country'] ?? '');
        } else {
            $label = sprintf('%.4f, %.4f', (float) $lat, (float) $lon);
        }

        $forecast = Http::timeout(15)->get('https://api.open-meteo.com/v1/forecast', [
            'latitude' => $lat,
            'longitude' => $lon,
            'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
            'daily' => 'temperature_2m_max,temperature_2m_min,weather_code',
            'timezone' => 'auto',
        ]);

        if (! $forecast->successful()) {
            return ['success' => false, 'error' => 'Weather API error: '.$forecast->body()];
        }

        $j = $forecast->json();
        $cur = $j['current'] ?? [];
        $codes = [
            0 => 'Clear', 1 => 'Mainly clear', 2 => 'Partly cloudy', 3 => 'Overcast',
            45 => 'Fog', 48 => 'Fog', 51 => 'Drizzle', 61 => 'Rain', 71 => 'Snow', 95 => 'Thunderstorm',
        ];
        $code = (int) ($cur['weather_code'] ?? 0);
        $summary = $codes[$code] ?? 'Weather code '.$code;

        $text = "Location: {$label}\n";
        $text .= 'Current: '.($cur['temperature_2m'] ?? '?').'°C, '.$summary;
        $text .= ', humidity '.($cur['relative_humidity_2m'] ?? '?').'%';
        $text .= ', wind '.($cur['wind_speed_10m'] ?? '?')." km/h\n";

        if (! empty($j['daily']['time'])) {
            $text .= "\nNext days:\n";
            foreach ($j['daily']['time'] as $i => $day) {
                if ($i >= 5) {
                    break;
                }
                $max = $j['daily']['temperature_2m_max'][$i] ?? '?';
                $min = $j['daily']['temperature_2m_min'][$i] ?? '?';
                $text .= "  {$day}: {$min}–{$max}°C\n";
            }
        }

        return ['success' => true, 'result' => $text];
    }
}
