<?php

namespace App\Services\Analytics;

/**
 * Rough USD estimates for dashboards (configure in config/pricing.php).
 */
final class TokenCostEstimator
{
    /**
     * @return array{input_per_million: float, output_per_million: float}
     */
    public function ratesForModel(string $model): array
    {
        $models = config('pricing.models', []);
        $needle = mb_strtolower($model);
        $best = $models['default'] ?? ['input_per_million' => 2.0, 'output_per_million' => 8.0];
        $bestLen = 0;

        foreach ($models as $key => $rates) {
            if ($key === 'default' || ! is_array($rates)) {
                continue;
            }
            $k = mb_strtolower((string) $key);
            if ($k !== '' && str_contains($needle, $k) && strlen($k) > $bestLen) {
                $best = [
                    'input_per_million' => (float) ($rates['input_per_million'] ?? 0),
                    'output_per_million' => (float) ($rates['output_per_million'] ?? 0),
                ];
                $bestLen = strlen($k);
            }
        }

        return $best;
    }

    public function estimateUsd(string $model, int $inputTokens, int $outputTokens): float
    {
        $r = $this->ratesForModel($model);
        $in = ($inputTokens / 1_000_000) * $r['input_per_million'];
        $out = ($outputTokens / 1_000_000) * $r['output_per_million'];

        return round($in + $out, 6);
    }
}
