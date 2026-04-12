<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsageEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    private function dateColumnExpr(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : 'DATE(created_at)';
    }

    public function summary(Request $request): JsonResponse
    {
        $days = min(365, max(1, (int) $request->query('days', 30)));
        $since = Carbon::now()->subDays($days)->startOfDay();

        $base = UsageEvent::query()->where('user_id', $request->user()->id)->where('created_at', '>=', $since);

        $totals = (clone $base)->selectRaw(
            'COALESCE(SUM(input_tokens), 0) as input_tokens, COALESCE(SUM(output_tokens), 0) as output_tokens, COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost_usd, COUNT(*) as events'
        )->first();

        $byModel = (clone $base)
            ->selectRaw('model, COALESCE(SUM(input_tokens), 0) as input_tokens, COALESCE(SUM(output_tokens), 0) as output_tokens, COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost_usd')
            ->groupBy('model')
            ->orderByDesc(DB::raw('input_tokens + output_tokens'))
            ->get();

        $bySource = (clone $base)
            ->selectRaw('source, COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost_usd, COUNT(*) as events')
            ->groupBy('source')
            ->get();

        return response()->json([
            'period_days' => $days,
            'since' => $since->toIso8601String(),
            'totals' => [
                'input_tokens' => (int) ($totals->input_tokens ?? 0),
                'output_tokens' => (int) ($totals->output_tokens ?? 0),
                'estimated_cost_usd' => (float) ($totals->estimated_cost_usd ?? 0),
                'events' => (int) ($totals->events ?? 0),
            ],
            'by_model' => $byModel,
            'by_source' => $bySource,
        ]);
    }

    public function timeseries(Request $request): JsonResponse
    {
        $days = min(365, max(1, (int) $request->query('days', 30)));
        $since = Carbon::now()->subDays($days)->startOfDay();

        $d = $this->dateColumnExpr();
        $rows = UsageEvent::query()
            ->where('user_id', $request->user()->id)
            ->where('created_at', '>=', $since)
            ->selectRaw("{$d} as d, COALESCE(SUM(input_tokens), 0) as input_tokens, COALESCE(SUM(output_tokens), 0) as output_tokens, COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost_usd")
            ->groupBy(DB::raw($d))
            ->orderBy('d')
            ->get();

        return response()->json([
            'period_days' => $days,
            'points' => $rows->map(fn ($r) => [
                'date' => $r->d,
                'input_tokens' => (int) $r->input_tokens,
                'output_tokens' => (int) $r->output_tokens,
                'estimated_cost_usd' => (float) $r->estimated_cost_usd,
            ]),
        ]);
    }
}
