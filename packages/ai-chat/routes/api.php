<?php

use AiChat\Http\Controllers\AiChatController;
use AiChat\Http\Controllers\ToolController;
use Illuminate\Support\Facades\Route;

Route::prefix('ai-chat')->middleware(['ai-chat.rate-limit', 'ai-chat.resolve-user'])->group(function () {
    Route::post('messages', [AiChatController::class, 'sendMessage']);
    Route::post('messages/stream', [AiChatController::class, 'streamMessage']);
    Route::get('conversations', [AiChatController::class, 'listConversations']);
    Route::get('conversations/{id}', [AiChatController::class, 'getConversation']);
    Route::delete('conversations/{id}', [AiChatController::class, 'deleteConversation']);
    Route::post('feedback', [AiChatController::class, 'submitFeedback']);

    Route::get('tools', [ToolController::class, 'listTools']);
    Route::get('tools/{name}', [ToolController::class, 'getToolSchema']);
});
