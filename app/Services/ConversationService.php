<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConversationService
{
    public function listForUser(User $user)
    {
        return $user->conversations()
            ->with(['participants', 'latestMessage'])
            ->orderByDesc(
                DB::table('messages')
                    ->select('created_at')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->latest()
                    ->limit(1)
            )
            ->get();
    }

    public function startPrivate(User $user, int $otherUserId): Conversation
    {
        $existing = Conversation::where('type', 'private')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $otherUserId))
            ->first();

        if ($existing) {
            return $existing->load(['participants', 'latestMessage']);
        }

        return DB::transaction(function () use ($user, $otherUserId) {
            $conversation = Conversation::create([
                'type' => 'private',
                'created_by' => $user->id,
            ]);

            $conversation->participants()->attach([
                $user->id => ['role' => 'member', 'joined_at' => now()],
                $otherUserId => ['role' => 'member', 'joined_at' => now()],
            ]);

            return $conversation->load(['participants', 'latestMessage']);
        });
    }

    public function createGroup(User $user, array $data): Conversation
    {
        return DB::transaction(function () use ($user, $data) {
            $conversation = Conversation::create([
                'type' => 'group',
                'name' => $data['name'],
                'avatar' => $data['avatar'] ?? null,
                'created_by' => $user->id,
            ]);

            $participants = [
                $user->id => ['role' => 'admin', 'joined_at' => now()],
            ];

            foreach ($data['participant_ids'] as $participantId) {
                $participants[$participantId] = ['role' => 'member', 'joined_at' => now()];
            }

            $conversation->participants()->attach($participants);

            return $conversation->load(['participants', 'latestMessage']);
        });
    }

    public function getDetail(User $user, int $conversationId): Conversation
    {
        $conversation = Conversation::with(['participants', 'latestMessage'])
            ->findOrFail($conversationId);

        $this->ensureUserIsParticipant($user, $conversation);

        return $conversation;
    }

    public function ensureUserIsParticipant(User $user, Conversation $conversation): void
    {
        $isParticipant = $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();

        if (! $isParticipant) {
            throw ValidationException::withMessages([
                'conversation' => ['Kamu bukan anggota percakapan ini.'],
            ]);
        }
    }
}