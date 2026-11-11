<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupEventResource;
use App\Http\Resources\PollResource;
use App\Models\Conversation;
use App\Models\GroupEvent;
use App\Models\Poll;
use App\Services\GroupPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupPlanController extends Controller
{
    public function __construct(protected GroupPlanService $plans)
    {
    }

    public function events(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        return response()->json([
            'events' => GroupEventResource::collection($this->plans->upcomingEvents($request->user(), $conversation)),
        ]);
    }

    public function storeEvent(Request $request, int $conversationId): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:160'],
            'starts_at' => ['required', 'date', 'after:now'],
        ]);

        $event = $this->plans->createEvent($request->user(), Conversation::findOrFail($conversationId), $data);

        return response()->json(['event' => new GroupEventResource($event)], 201);
    }

    public function updateEvent(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'location' => ['sometimes', 'nullable', 'string', 'max:160'],
            'starts_at' => ['sometimes', 'date'],
        ]);

        $event = $this->plans->updateEvent($request->user(), GroupEvent::findOrFail($id), $data);

        return response()->json(['event' => new GroupEventResource($event)]);
    }

    public function destroyEvent(Request $request, int $id): JsonResponse
    {
        $this->plans->deleteEvent($request->user(), GroupEvent::findOrFail($id));

        return response()->json(['message' => 'Acara dihapus.']);
    }

    public function respond(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:going,maybe,declined']]);

        $event = $this->plans->respond($request->user(), GroupEvent::findOrFail($id), $data['status']);

        return response()->json(['event' => new GroupEventResource($event)]);
    }

    public function polls(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        return response()->json([
            'polls' => PollResource::collection($this->plans->polls($request->user(), $conversation)),
        ]);
    }

    public function storePoll(Request $request, int $conversationId): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:200'],
            'options' => ['required', 'array', 'min:2', 'max:8'],
            'options.*' => ['required', 'string', 'max:120', 'distinct'],
        ]);

        $poll = $this->plans->createPoll(
            $request->user(),
            Conversation::findOrFail($conversationId),
            $data['question'],
            $data['options'],
        );

        return response()->json(['poll' => new PollResource($poll)], 201);
    }

    public function vote(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['option_id' => ['required', 'integer']]);

        $poll = $this->plans->vote($request->user(), Poll::findOrFail($id), $data['option_id']);

        return response()->json(['poll' => new PollResource($poll)]);
    }

    public function destroyPoll(Request $request, int $id): JsonResponse
    {
        $this->plans->deletePoll($request->user(), Poll::findOrFail($id));

        return response()->json(['message' => 'Polling dihapus.']);
    }
}
