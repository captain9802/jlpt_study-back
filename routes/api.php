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

            Route::get('/favorites/grammar-lists', [FavoriteListController::class, 'getGrammarLists']);
            Route::post('/favorites/grammar-lists', [FavoriteListController::class, 'storeGrammarList']);
            Route::put('/favorites/grammar-lists/{id}', [FavoriteListController::class, 'updateGrammarList']);
            Route::delete('/favorites/grammar-lists/{id}', [FavoriteListController::class, 'deleteGrammarList']);

            Route::get('/favorites/grammars', [FavoriteController::class, 'getAllGrammarTexts']);
            Route::get('/favorites/grammars/{list_id}', [FavoriteController::class, 'getFavoriteGrammars']);
            Route::post('/favorites/grammars/toggle', [FavoriteController::class, 'toggleGrammarFavorite']);

            Route::get('/favorites/sentence-lists', [FavoriteListController::class, 'getSentenceLists']);
            Route::post('/favorites/sentence-lists', [FavoriteListController::class, 'storeSentenceList']);
            Route::put('/favorites/sentence-lists/{id}', [FavoriteListController::class, 'updateSentenceList']);
            Route::delete('/favorites/sentence-lists/{id}', [FavoriteListController::class, 'deleteSentenceList']);

            Route::get('/favorites/sentences', [FavoriteController::class, 'getAllSentenceTexts']);
            Route::get('/favorites/sentences/{list_id}', [FavoriteController::class, 'getFavoriteSentences']);
            Route::post('/favorites/sentences/toggle', [FavoriteController::class, 'toggleSentenceFavorite']);

        });
    });


