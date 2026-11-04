<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\MessageResource;
use App\Services\ConversationService;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService,
        protected ConversationService $conversationService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'conversation_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'types' => ['nullable', 'array'],
            'types.*' => ['in:messages,files'],
        ]);

        $user = $request->user();
        $result = $this->searchService->search($user, trim($data['q']), $data);

        return response()->json([
            'query' => $data['q'],
            'messages' => $result['messages']->map(fn ($m) => [
                'message' => new MessageResource($m),
                'conversation' => [
                    'id' => $m->conversation->id,
                    'name' => $this->conversationService->displayName($m->conversation, $user),
                    'type' => $m->conversation->type,
                ],
            ])->values(),
            'files' => $result['files']->map(fn ($a) => [
                'attachment' => new AttachmentResource($a),
                'conversation' => [
                    'id' => $a->message->conversation->id,
                    'name' => $this->conversationService->displayName($a->message->conversation, $user),
                    'type' => $a->message->conversation->type,
                ],
            ])->values(),
            'counts' => [
                'messages' => $result['messages']->count(),
                'files' => $result['files']->count(),
            ],
        ]);
    }
}
