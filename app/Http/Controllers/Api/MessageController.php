<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
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

        $page = $this->messageService->getMessages(
            $request->user(),
            $conversation,
            $request->integer('before') ?: null,
        );

        return response()->json([
            'messages' => MessageResource::collection($page['messages']),
            'meta' => [
                'has_more' => $page['has_more'],
                'oldest_id' => $page['messages']->last()?->id,
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $message = Message::findOrFail($id);

        return response()->json([
            'data' => new MessageResource($this->messageService->edit($request->user(), $message, $data['body'])),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        return response()->json([
            'data' => new MessageResource($this->messageService->delete($request->user(), $message)),
        ]);
    }

    public function react(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['emoji' => ['required', 'string', 'max:16']]);
        $message = Message::findOrFail($id);

        return response()->json([
            'data' => new MessageResource($this->messageService->toggleReaction($request->user(), $message, $data['emoji'])),
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