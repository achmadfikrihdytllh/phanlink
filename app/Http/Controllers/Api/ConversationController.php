<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGroupRequest;
use App\Http\Requests\StartPrivateConversationRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
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

    /** Group conversations only: the "Groups" page. */
    public function groups(Request $request): JsonResponse
    {
        return response()->json([
            'groups' => ConversationResource::collection(
                $this->conversationService->groupsForUser($request->user())
            ),
        ]);
    }

    /** Mute / pin a conversation for the signed-in user. */
    public function preferences(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'is_muted' => ['sometimes', 'boolean'],
            'is_pinned' => ['sometimes', 'boolean'],
        ]);

        $conversation = Conversation::findOrFail($id);

        return response()->json([
            'conversation' => new ConversationResource(
                $this->conversationService->updatePreferences($request->user(), $conversation, $data)
            ),
        ]);
    }

    /** Invite people to a group (admins only). */
    public function addParticipants(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $conversation = Conversation::findOrFail($id);

        return response()->json([
            'message' => 'Anggota ditambahkan.',
            'conversation' => new ConversationResource(
                $this->conversationService->addParticipants($request->user(), $conversation, $data['user_ids'])
            ),
        ]);
    }

    /** Files shared in this conversation. */
    public function attachments(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        return response()->json([
            'attachments' => AttachmentResource::collection(
                $this->conversationService->attachments($request->user(), $conversation)
            ),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversationService->getDetail($request->user(), $id);

        return response()->json([
            'conversation' => new ConversationResource($conversation),
        ]);
    }
}