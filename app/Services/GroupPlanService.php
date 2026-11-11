<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\EventResponse;
use App\Models\GroupEvent;
use App\Models\Message;
use App\Models\Poll;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Group events (with RSVP) and polls. Every member can read and respond; the creator or an admin manages. */
class GroupPlanService
{
    public function __construct(protected ConversationService $conversations)
    {
    }

    // ---- events ----

    public function upcomingEvents(User $user, Conversation $conversation)
    {
        $this->conversations->ensureUserIsParticipant($user, $conversation);

        return $conversation->hasMany(GroupEvent::class)
            ->with(['creator', 'responses'])
            ->where('starts_at', '>=', now()->subHours(3)) // keep an event visible a little after it starts
            ->orderBy('starts_at')
            ->get();
    }

    public function createEvent(User $user, Conversation $conversation, array $data): GroupEvent
    {
        $this->ensureGroup($user, $conversation);

        $event = $conversation->hasMany(GroupEvent::class)->create([
            'conversation_id' => $conversation->id,
            'created_by' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'starts_at' => $data['starts_at'],
        ]);

        // The creator is going by default.
        $event->responses()->create(['user_id' => $user->id, 'status' => 'going']);

        $when = $event->starts_at->timezone(config('app.timezone'))->format('d M Y H:i');
        $this->announce($conversation, $user, "Acara baru: {$event->title} · {$when}" . ($event->location ? " · {$event->location}" : ''));

        return $event->load(['creator', 'responses']);
    }

    public function updateEvent(User $user, GroupEvent $event, array $data): GroupEvent
    {
        $this->ensureCanManage($user, $event->conversation, $event->created_by);

        $event->update(array_intersect_key($data, array_flip(['title', 'description', 'location', 'starts_at'])));

        return $event->load(['creator', 'responses']);
    }

    public function deleteEvent(User $user, GroupEvent $event): void
    {
        $this->ensureCanManage($user, $event->conversation, $event->created_by);

        $event->delete();
    }

    public function respond(User $user, GroupEvent $event, string $status): GroupEvent
    {
        $this->conversations->ensureUserIsParticipant($user, $event->conversation);

        EventResponse::updateOrCreate(
            ['group_event_id' => $event->id, 'user_id' => $user->id],
            ['status' => $status],
        );

        return $event->load(['creator', 'responses']);
    }

    // ---- polls ----

    public function polls(User $user, Conversation $conversation)
    {
        $this->conversations->ensureUserIsParticipant($user, $conversation);

        return Poll::where('conversation_id', $conversation->id)
            ->with(['options', 'votes'])
            ->latest()
            ->limit(20)
            ->get();
    }

    public function createPoll(User $user, Conversation $conversation, string $question, array $options): Poll
    {
        $this->ensureGroup($user, $conversation);

        $poll = DB::transaction(function () use ($user, $conversation, $question, $options) {
            $poll = Poll::create([
                'conversation_id' => $conversation->id,
                'created_by' => $user->id,
                'question' => $question,
            ]);

            foreach ($options as $label) {
                $poll->options()->create(['label' => $label]);
            }

            return $poll;
        });

        $this->announce($conversation, $user, "Polling baru: {$question}");

        return $poll->load(['options', 'votes']);
    }

    /** One vote per person; voting again moves the vote. */
    public function vote(User $user, Poll $poll, int $optionId): Poll
    {
        $this->conversations->ensureUserIsParticipant($user, Conversation::findOrFail($poll->conversation_id));

        if (! $poll->options()->where('id', $optionId)->exists()) {
            throw ValidationException::withMessages(['option_id' => ['Pilihan tidak ada di polling ini.']]);
        }

        $poll->votes()->updateOrCreate(['user_id' => $user->id], ['poll_option_id' => $optionId]);

        return $poll->load(['options', 'votes']);
    }

    public function deletePoll(User $user, Poll $poll): void
    {
        $this->ensureCanManage($user, Conversation::findOrFail($poll->conversation_id), $poll->created_by);

        $poll->delete();
    }

    // ---- helpers ----

    private function ensureGroup(User $user, Conversation $conversation): void
    {
        $this->conversations->ensureUserIsParticipant($user, $conversation);

        if ($conversation->type !== 'group') {
            throw ValidationException::withMessages(['conversation' => ['Fitur ini hanya untuk group.']]);
        }
    }

    private function ensureCanManage(User $user, Conversation $conversation, int $creatorId): void
    {
        $this->conversations->ensureUserIsParticipant($user, $conversation);

        if ($user->id === $creatorId) {
            return;
        }

        $this->conversations->ensureGroupAdmin($user, $conversation);
    }

    /** Post a short note in the chat so members notice the new plan. */
    private function announce(Conversation $conversation, User $author, string $text): void
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $author->id,
            'type' => 'text',
            'body' => $text,
        ]);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
