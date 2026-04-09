<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentReminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentReminderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()
            ->agentReminders()
            ->orderByRaw('read_at is null desc')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json($items);
    }

    public function ack(Request $request, AgentReminder $agentReminder): JsonResponse
    {
        abort_if($agentReminder->user_id !== $request->user()->id, 403);

        $agentReminder->update(['read_at' => now()]);

        return response()->json($agentReminder);
    }
}
