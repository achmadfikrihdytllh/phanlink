<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations/private', [ConversationController::class, 'startPrivate']);
    Route::post('/conversations/group', [ConversationController::class, 'createGroup']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
});