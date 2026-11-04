<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\SavedController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SettingController;
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

    Route::get('/groups', [ConversationController::class, 'groups']);

    Route::get('/contacts', [ContactController::class, 'index']);
    Route::post('/contacts', [ContactController::class, 'store']);
    Route::get('/contacts/{userId}', [ContactController::class, 'show']);
    Route::patch('/contacts/{userId}', [ContactController::class, 'update']);
    Route::delete('/contacts/{userId}', [ContactController::class, 'destroy']);

    Route::get('/saved', [SavedController::class, 'index']);
    Route::post('/saved', [SavedController::class, 'store']);
    Route::get('/saved/collections', [SavedController::class, 'collections']);
    Route::post('/saved/collections', [SavedController::class, 'storeCollection']);
    Route::delete('/saved/collections/{id}', [SavedController::class, 'destroyCollection']);
    Route::delete('/saved/{id}', [SavedController::class, 'destroy']);

    Route::get('/search', [SearchController::class, 'index']);

    Route::get('/settings', [SettingController::class, 'show']);
    Route::put('/settings', [SettingController::class, 'update']);

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::patch('/conversations/{id}/preferences', [ConversationController::class, 'preferences']);
    Route::post('/conversations/{id}/participants', [ConversationController::class, 'addParticipants']);
    Route::get('/conversations/{id}/attachments', [ConversationController::class, 'attachments']);
    Route::post('/conversations/private', [ConversationController::class, 'startPrivate']);
    Route::post('/conversations/group', [ConversationController::class, 'createGroup']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);

    Route::get('/conversations/{conversationId}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversationId}/messages', [MessageController::class, 'store']);
    Route::post('/conversations/{conversationId}/read', [MessageController::class, 'markAsRead']);
});