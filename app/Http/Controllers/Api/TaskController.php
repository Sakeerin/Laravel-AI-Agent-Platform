<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskLog;
use App\Models\ToolCall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = $request->user()
            ->taskLogs()
            ->when($request->query('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->query('conversation_id'), fn($q, $c) => $q->where('conversation_id', $c))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($tasks);
    }

    public function show(Request $request, TaskLog $taskLog): JsonResponse
    {
        abort_if($taskLog->user_id !== $request->user()->id, 403);

        return response()->json($taskLog);
    }

    public function cancel(Request $request, TaskLog $taskLog): JsonResponse
    {
        abort_if($taskLog->user_id !== $request->user()->id, 403);

        if (in_array($taskLog->status, ['queued', 'running'])) {
            $taskLog->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);
        }

        return response()->json($taskLog);
    }

    public function toolCalls(Request $request, int $conversationId): JsonResponse
    {
        $conversation = $request->user()
            ->conversations()
            ->findOrFail($conversationId);

        $toolCalls = ToolCall::where('conversation_id', $conversation->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($toolCalls);
    }
}
