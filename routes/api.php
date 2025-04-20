<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::post('api/login', [UserController::class, 'kakaoLogin']);

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [UserController::class, 'me']);

});
