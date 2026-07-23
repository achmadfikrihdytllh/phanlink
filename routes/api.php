<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/users/search', [UserController::class, 'search']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/profile', [UserController::class, 'updateProfile']);

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations/private', [ConversationController::class, 'startPrivate']);
    Route::post('/conversations/group', [ConversationController::class, 'createGroup']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);

    Route::get('/conversations/{conversationId}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversationId}/messages', [MessageController::class, 'store']);
    Route::post('/conversations/{conversationId}/read', [MessageController::class, 'markAsRead']);
});