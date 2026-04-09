<?php

use App\Http\Controllers\Api\AgentReminderController;
use App\Http\Controllers\Api\AgentSettingsController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChannelConnectionController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MemoryController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\Webhooks\DiscordWebhookController;
use App\Http\Controllers\Api\Webhooks\LineWebhookController;
use App\Http\Controllers\Api\Webhooks\SlackWebhookController;
use App\Http\Controllers\Api\Webhooks\TelegramWebhookController;
use App\Services\AI\AIManager;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/webhooks/line/{webhook_key}', LineWebhookController::class)
    ->middleware('throttle:channel-webhook');
Route::post('/webhooks/telegram/{webhook_key}', TelegramWebhookController::class)
    ->middleware('throttle:channel-webhook');
Route::post('/webhooks/slack/{webhook_key}', SlackWebhookController::class)
    ->middleware('throttle:channel-webhook');
Route::post('/webhooks/discord/{webhook_key}', DiscordWebhookController::class)
    ->middleware('throttle:channel-webhook');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::post('/chat', [ChatController::class, 'send']);
    Route::get('/chat/{conversation}/stream', [ChatController::class, 'stream']);

    Route::apiResource('conversations', ConversationController::class);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
    Route::get('/conversations/{conversation}/tool-calls', [TaskController::class, 'toolCalls']);

    Route::apiResource('api-keys', ApiKeyController::class)->except(['show', 'update']);

    Route::get('/models', function (AIManager $ai) {
        return response()->json($ai->availableModels());
    });

    Route::get('/skills', [SkillController::class, 'index']);
    Route::get('/skills/available', [SkillController::class, 'available']);
    Route::get('/skills/{skill}', [SkillController::class, 'show']);
    Route::patch('/skills/{skill}/toggle', [SkillController::class, 'toggle']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{taskLog}', [TaskController::class, 'show']);
    Route::post('/tasks/{taskLog}/cancel', [TaskController::class, 'cancel']);

    Route::get('/channel-connections', [ChannelConnectionController::class, 'index']);
    Route::get('/channel-connections/{channel_connection}', [ChannelConnectionController::class, 'show']);
    Route::post('/channel-connections', [ChannelConnectionController::class, 'store']);
    Route::patch('/channel-connections/{channel_connection}', [ChannelConnectionController::class, 'update']);
    Route::delete('/channel-connections/{channel_connection}', [ChannelConnectionController::class, 'destroy']);
    Route::post('/channel-connections/{channel_connection}/telegram/webhook', [ChannelConnectionController::class, 'registerTelegramWebhook']);

    Route::get('/agent-settings', [AgentSettingsController::class, 'show']);
    Route::patch('/agent-settings', [AgentSettingsController::class, 'update']);
    Route::get('/memories', [MemoryController::class, 'index']);
    Route::post('/memories', [MemoryController::class, 'store']);
    Route::delete('/memories/{user_memory}', [MemoryController::class, 'destroy']);
    Route::get('/reminders', [AgentReminderController::class, 'index']);
    Route::post('/reminders/{agent_reminder}/ack', [AgentReminderController::class, 'ack']);
});
