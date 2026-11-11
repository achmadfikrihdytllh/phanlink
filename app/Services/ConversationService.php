<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
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

        $this->ensureCanStartChat($user, $otherUserId);

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

    /** Honours the other person's "who can message me" setting when a new private chat is opened. */
    public function ensureCanStartChat(User $sender, int $otherUserId): void
    {
        $setting = User::find($otherUserId)?->settings;

        if (($setting?->who_can_message ?? 'contacts') !== 'contacts') {
            return;
        }

        $isTheirContact = Contact::where('user_id', $otherUserId)->where('contact_id', $sender->id)->exists();

        if (! $isTheirContact) {
            throw ValidationException::withMessages([
                'user_id' => ['Orang ini hanya menerima pesan dari kontaknya.'],
            ]);
        }
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

    /** Group conversations the user belongs to (the "Groups" page). */
    public function groupsForUser(User $user)
    {
        return $user->conversations()
            ->where('type', 'group')
            ->with(['participants', 'latestMessage'])
            ->get();
    }

    public function findPrivate(User $user, int $otherUserId): ?Conversation
    {
        return Conversation::where('type', 'private')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $otherUserId))
            ->first();
    }

    /** Group conversations that both users belong to. */
    public function groupsInCommon(User $user, int $otherUserId)
    {
        return Conversation::where('type', 'group')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $otherUserId))
            ->withCount('participants')
            ->get();
    }

    /** Mute / pin a conversation for the signed-in user only. */
    public function updatePreferences(User $user, Conversation $conversation, array $data): Conversation
    {
        $this->ensureUserIsParticipant($user, $conversation);

        $pivot = array_intersect_key($data, array_flip(['is_muted', 'is_pinned']));

        if ($pivot !== []) {
            $conversation->participants()->updateExistingPivot($user->id, $pivot);
        }

        return $conversation->load(['participants', 'latestMessage']);
    }

    /** Add people to a group. Only group admins may invite. */
    public function addParticipants(User $user, Conversation $conversation, array $userIds): Conversation
    {
        $this->ensureUserIsParticipant($user, $conversation);

        if ($conversation->type !== 'group') {
            throw ValidationException::withMessages([
                'conversation' => ['Hanya group yang bisa menambah anggota.'],
            ]);
        }

        $isAdmin = $conversation->participants()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'admin')
            ->exists();

        if (! $isAdmin) {
            throw ValidationException::withMessages([
                'conversation' => ['Hanya admin group yang bisa mengundang anggota.'],
            ]);
        }

        $existing = $conversation->participants()->pluck('users.id')->all();

        $new = [];
        foreach (array_diff($userIds, $existing) as $id) {
            $new[$id] = ['role' => 'member', 'joined_at' => now()];
        }

        if ($new !== []) {
            $conversation->participants()->attach($new);
        }

        return $conversation->load(['participants', 'latestMessage']);
    }

    /** Rename or re-describe a group. Admins only. */
    public function updateGroup(User $user, Conversation $conversation, array $data): Conversation
    {
        $this->ensureGroupAdmin($user, $conversation);

        $conversation->update(array_intersect_key($data, array_flip(['name', 'description'])));

        return $conversation->load(['participants', 'latestMessage']);
    }

    /**
     * Remove a member (admins) or leave the group (yourself).
     * If the last admin leaves, the longest-standing member is promoted so the group is never leaderless.
     */
    public function removeParticipant(User $actor, Conversation $conversation, int $userId): ?Conversation
    {
        $this->ensureUserIsParticipant($actor, $conversation);

        if ($conversation->type !== 'group') {
            throw ValidationException::withMessages(['conversation' => ['Hanya group yang punya anggota.']]);
        }

        if ($actor->id !== $userId) {
            $this->ensureGroupAdmin($actor, $conversation);
        }

        $conversation->participants()->detach($userId);

        $remaining = $conversation->participants()->orderBy('conversation_participants.joined_at')->get();

        if ($remaining->isNotEmpty() && ! $remaining->contains(fn ($u) => $u->pivot->role === 'admin')) {
            $conversation->participants()->updateExistingPivot($remaining->first()->id, ['role' => 'admin']);
        }

        return $actor->id === $userId ? null : $conversation->load(['participants', 'latestMessage']);
    }

    /** Pin one message to the top of the chat (null unpins). Groups: admins only. */
    public function pinMessage(User $user, Conversation $conversation, ?int $messageId): Conversation
    {
        $this->ensureUserIsParticipant($user, $conversation);

        if ($conversation->type === 'group') {
            $this->ensureGroupAdmin($user, $conversation);
        }

        if ($messageId !== null && ! Message::where('id', $messageId)->where('conversation_id', $conversation->id)->exists()) {
            throw ValidationException::withMessages(['message_id' => ['Pesan tidak ada di percakapan ini.']]);
        }

        $conversation->update(['pinned_message_id' => $messageId]);

        return $conversation->load(['participants', 'latestMessage', 'pinnedMessage']);
    }

    public function ensureGroupAdmin(User $user, Conversation $conversation): void
    {
        $this->ensureUserIsParticipant($user, $conversation);

        $isAdmin = $conversation->type === 'group' && $conversation->participants()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'admin')
            ->exists();

        if (! $isAdmin) {
            throw ValidationException::withMessages([
                'conversation' => ['Hanya admin group yang bisa melakukan ini.'],
            ]);
        }
    }

    /** Files shared in a conversation, newest first ("Shared intel" panel). */
    public function attachments(User $user, Conversation $conversation, int $limit = 20)
    {
        $this->ensureUserIsParticipant($user, $conversation);

        return MessageAttachment::query()
            ->whereHas('message', fn ($q) => $q->where('conversation_id', $conversation->id))
            ->with('message.sender')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /** Name shown to $viewer: the other person for private chats, the group name otherwise. */
    public function displayName(Conversation $conversation, User $viewer): ?string
    {
        if ($conversation->type === 'private') {
            return $conversation->participants->firstWhere('id', '!=', $viewer->id)?->name;
        }

        return $conversation->name;
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