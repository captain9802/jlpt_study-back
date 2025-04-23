<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatController;

Route::post('/api/login', [UserController::class, 'kakaoLogin']);

Route::middleware([\Illuminate\Http\Middleware\HandleCors::class])
    ->prefix('api')
    ->group(function() {
        Route::middleware(['auth:api'])->group(function () {
            Route::get('/ai-settings', [UserController::class, 'getSettings']);
            Route::post('/ai-settings', [UserController::class, 'saveSettings']);
            Route::post('/chat/tooltip', [ChatController::class, 'tooltip']);
            Route::get('/chat-memories', [ChatController::class, 'getMemories']);
            Route::put('/ai-settings/language-mode', [UserController::class, 'updateLanguageMode']);
            Route::post('/chat-memories', [ChatController::class, 'saveSummary']);
            Route::post('/chat', [ChatController::class, 'sendMessage']);
        });
    });


