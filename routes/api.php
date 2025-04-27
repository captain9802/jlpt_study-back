<?php

use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FavoriteListController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatController;

Route::post('/api/login', [UserController::class, 'kakaoLogin']);

Route::middleware([\Illuminate\Http\Middleware\HandleCors::class])
    ->prefix('api')
    ->group(function () {
        Route::middleware(['auth:api'])->group(function () {
            // 기존 라우트
            Route::get('/ai-settings', [UserController::class, 'getSettings']);
            Route::post('/ai-settings', [UserController::class, 'saveSettings']);
            Route::put('/ai-settings/language-mode', [UserController::class, 'updateLanguageMode']);
            Route::post('/chat/tooltip', [ChatController::class, 'tooltip']);
            Route::get('/chat-memories', [ChatController::class, 'getMemories']);
            Route::post('/chat-memories', [ChatController::class, 'saveSummary']);
            Route::post('/chat', [ChatController::class, 'sendMessage']);

            Route::get('/favorites/lists', [FavoriteListController::class, 'index']);
            Route::post('/favorites/lists', [FavoriteListController::class, 'store']);
            Route::put('/favorites/lists/{id}', [FavoriteListController::class, 'update']);
            Route::delete('/favorites/lists/{id}', [FavoriteListController::class, 'destroy']);

            Route::get('/favorites/words', [FavoriteController::class, 'getFavoriteWords']);
            Route::get('/favorites/words/{listId}', [FavoriteController::class, 'index']);
            Route::post('/favorites/words/toggle', [FavoriteController::class, 'toggleFavorite']);
        });
    });


