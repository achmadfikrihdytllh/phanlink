<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGroupRequest;
use App\Http\Requests\StartPrivateConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(protected ConversationService $conversationService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $conversations = $this->conversationService->listForUser($request->user());

        return response()->json([
            'conversations' => ConversationResource::collection($conversations),
        ]);
    }

    public function startPrivate(StartPrivateConversationRequest $request): JsonResponse
    {
        $conversation = $this->conversationService->startPrivate(
            $request->user(),
            $request->validated('user_id')
        );

        return response()->json([
            'message' => 'Percakapan siap.',
            'conversation' => new ConversationResource($conversation),
        ]);
    }

    public function createGroup(CreateGroupRequest $request): JsonResponse
    {
        $conversation = $this->conversationService->createGroup(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Group berhasil dibuat.',
            'conversation' => new ConversationResource($conversation),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversationService->getDetail($request->user(), $id);

        return response()->json([
            'conversation' => new ConversationResource($conversation),
        ]);
    }
}