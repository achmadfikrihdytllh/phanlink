<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\GroupPlanController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\SavedController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public endpoints are throttled hard: they are the ones a stranger can hammer.
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordController::class, 'forgot']);
    Route::post('/reset-password', [PasswordController::class, 'reset']);
});

Route::middleware(['auth:sanctum', \App\Http\Middleware\TouchLastSeen::class, 'throttle:240,1'])->group(function () {
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
    Route::patch('/conversations/{id}', [ConversationController::class, 'update']);
    Route::delete('/conversations/{id}/participants/{userId}', [ConversationController::class, 'removeParticipant']);
    Route::post('/conversations/{id}/pin', [ConversationController::class, 'pin']);

    Route::get('/conversations/{conversationId}/events', [GroupPlanController::class, 'events']);
    Route::post('/conversations/{conversationId}/events', [GroupPlanController::class, 'storeEvent']);
    Route::patch('/events/{id}', [GroupPlanController::class, 'updateEvent']);
    Route::delete('/events/{id}', [GroupPlanController::class, 'destroyEvent']);
    Route::post('/events/{id}/respond', [GroupPlanController::class, 'respond']);

    Route::get('/conversations/{conversationId}/polls', [GroupPlanController::class, 'polls']);
    Route::post('/conversations/{conversationId}/polls', [GroupPlanController::class, 'storePoll']);
    Route::post('/polls/{id}/vote', [GroupPlanController::class, 'vote']);
    Route::delete('/polls/{id}', [GroupPlanController::class, 'destroyPoll']);

    Route::get('/conversations/{conversationId}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversationId}/messages', [MessageController::class, 'store'])->middleware('throttle:60,1');
    Route::patch('/messages/{id}', [MessageController::class, 'update']);
    Route::delete('/messages/{id}', [MessageController::class, 'destroy']);
    Route::post('/messages/{id}/reactions', [MessageController::class, 'react']);
    Route::post('/conversations/{conversationId}/read', [MessageController::class, 'markAsRead']);
});