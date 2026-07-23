<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(protected MessageService $messageService)
    {
    }

    public function index(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        $messages = $this->messageService->getMessages($request->user(), $conversation);

        return response()->json([
            'messages' => MessageResource::collection($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function store(SendMessageRequest $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        $message = $this->messageService->send(
            $request->user(),
            $conversation,
            $request->validated()
        );

        return response()->json([
            'message' => 'Pesan terkirim.',
            'data' => new MessageResource($message),
        ], 201);
    }

    public function markAsRead(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        $this->messageService->markAsRead($request->user(), $conversation);

        return response()->json([
            'message' => 'Percakapan ditandai sudah dibaca.',
        ]);
    }
}