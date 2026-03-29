<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Services\AI\AIManager;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::post('/chat', [ChatController::class, 'send']);
    Route::get('/chat/{conversation}/stream', [ChatController::class, 'stream']);

    Route::apiResource('conversations', ConversationController::class);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);

    Route::apiResource('api-keys', ApiKeyController::class)->except(['show', 'update']);

    Route::get('/models', function (AIManager $ai) {
        return response()->json($ai->availableModels());
    });
});
