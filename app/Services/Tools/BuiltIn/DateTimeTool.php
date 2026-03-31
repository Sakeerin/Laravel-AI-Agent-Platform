<?php

namespace App\Services\Tools\BuiltIn;

use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;
use Carbon\Carbon;

class DateTimeTool extends BaseTool
{
    public function name(): string
    {
        return 'datetime';
    }

    public function displayName(): string
    {
        return 'Date & Time';
    }

    public function description(): string
    {
        return 'Get current date/time, convert timezones, calculate date differences, or parse date strings.';
    }

    public function category(): string
    {
        return 'utility';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['now', 'convert', 'diff', 'parse', 'add', 'subtract'],
                    'description' => 'The operation to perform',
                ],
                'timezone' => [
                    'type' => 'string',
                    'description' => 'Timezone (e.g. Asia/Bangkok, America/New_York, UTC)',
                ],
                'date' => [
                    'type' => 'string',
                    'description' => 'A date/time string to parse or use as base',
                ],
                'target_timezone' => [
                    'type' => 'string',
                    'description' => 'Target timezone for conversion',
                ],
                'date2' => [
                    'type' => 'string',
                    'description' => 'Second date for diff calculation',
                ],
                'amount' => [
                    'type' => 'integer',
                    'description' => 'Amount to add/subtract',
                ],
                'unit' => [
                    'type' => 'string',
                    'enum' => ['seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years'],
                    'description' => 'Unit for add/subtract',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $arguments, ?ToolContext $context = null): array
    {
        $action = $arguments['action'] ?? 'now';

        try {
            return match ($action) {
                'now' => $this->getCurrentTime($arguments['timezone'] ?? 'UTC'),
                'convert' => $this->convertTimezone($arguments),
                'diff' => $this->dateDiff($arguments),
                'parse' => $this->parseDate($arguments),
                'add' => $this->addTime($arguments),
                'subtract' => $this->subtractTime($arguments),
                default => ['success' => false, 'result' => null, 'error' => "Unknown action: {$action}"],
            };
        } catch (\Exception $e) {
            return ['success' => false, 'result' => null, 'error' => $e->getMessage()];
        }
    }

    private function getCurrentTime(string $timezone): array
    {
        $now = Carbon::now($timezone);
        return [
            'success' => true,
            'result' => [
                'datetime' => $now->toDateTimeString(),
                'iso8601' => $now->toIso8601String(),
                'timezone' => $timezone,
                'unix_timestamp' => $now->timestamp,
                'day_of_week' => $now->format('l'),
            ],
        ];
    }

    private function convertTimezone(array $args): array
    {
        $date = Carbon::parse($args['date'] ?? 'now', $args['timezone'] ?? 'UTC');
        $target = $args['target_timezone'] ?? 'UTC';
        $converted = $date->setTimezone($target);

        return [
            'success' => true,
            'result' => [
                'original' => $date->toDateTimeString() . ' (' . ($args['timezone'] ?? 'UTC') . ')',
                'converted' => $converted->toDateTimeString() . ' (' . $target . ')',
            ],
        ];
    }

    private function dateDiff(array $args): array
    {
        $date1 = Carbon::parse($args['date'] ?? 'now');
        $date2 = Carbon::parse($args['date2'] ?? 'now');

        return [
            'success' => true,
            'result' => [
                'date1' => $date1->toDateTimeString(),
                'date2' => $date2->toDateTimeString(),
                'diff_days' => $date1->diffInDays($date2),
                'diff_hours' => $date1->diffInHours($date2),
                'diff_human' => $date1->diffForHumans($date2),
            ],
        ];
    }

    private function parseDate(array $args): array
    {
        $date = Carbon::parse($args['date'] ?? 'now', $args['timezone'] ?? null);

        return [
            'success' => true,
            'result' => [
                'parsed' => $date->toDateTimeString(),
                'iso8601' => $date->toIso8601String(),
                'unix_timestamp' => $date->timestamp,
                'day_of_week' => $date->format('l'),
                'week_of_year' => $date->weekOfYear,
            ],
        ];
    }

    private function addTime(array $args): array
    {
        $date = Carbon::parse($args['date'] ?? 'now', $args['timezone'] ?? null);
        $amount = $args['amount'] ?? 0;
        $unit = $args['unit'] ?? 'days';

        $result = $date->add($amount, $unit);

        return [
            'success' => true,
            'result' => [
                'original' => Carbon::parse($args['date'] ?? 'now')->toDateTimeString(),
                'result' => $result->toDateTimeString(),
                'added' => "{$amount} {$unit}",
            ],
        ];
    }

    private function subtractTime(array $args): array
    {
        $date = Carbon::parse($args['date'] ?? 'now', $args['timezone'] ?? null);
        $amount = $args['amount'] ?? 0;
        $unit = $args['unit'] ?? 'days';

        $result = $date->sub($amount, $unit);

        return [
            'success' => true,
            'result' => [
                'original' => Carbon::parse($args['date'] ?? 'now')->toDateTimeString(),
                'result' => $result->toDateTimeString(),
                'subtracted' => "{$amount} {$unit}",
            ],
        ];
    }
}
