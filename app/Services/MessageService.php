<?php

namespace App\Services;

use App\Events\MessageChanged;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MessageService
{
    public function __construct(protected ConversationService $conversationService)
    {
    }

    public function send(User $user, Conversation $conversation, array $data): Message
    {
        $this->conversationService->ensureUserIsParticipant($user, $conversation);

        if (! empty($data['reply_to_id'])) {
            $sameConversation = Message::withTrashed()
                ->where('id', $data['reply_to_id'])
                ->where('conversation_id', $conversation->id)
                ->exists();

            if (! $sameConversation) {
                throw ValidationException::withMessages([
                    'reply_to_id' => ['Pesan yang dibalas harus ada di percakapan yang sama.'],
                ]);
            }
        }

        $message = DB::transaction(function () use ($user, $conversation, $data) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'type' => $data['type'],
                'body' => $data['body'] ?? null,
                'reply_to_id' => $data['reply_to_id'] ?? null,
            ]);

            if (isset($data['attachment'])) {
                $file = $data['attachment'];
                $path = $file->store('attachments/' . $conversation->id, config('filesystems.uploads'));

                $message->attachments()->create([
                    'file_path' => Storage::disk(config('filesystems.uploads'))->url($path),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }

            $conversation->participants()->updateExistingPivot($user->id, [
                'last_read_at' => now(),
            ]);

            return $message->load(['sender', 'replyTo', 'attachments', 'reactions']);
        });

        // The message is already saved; a realtime hiccup (e.g. Reverb not running)
        // must not turn a successful send into a 500.
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }

        return $message;
    }

    /**
     * Newest-first page of messages. Pass $beforeId to load the page of older messages
     * that comes before that message (infinite scroll upwards).
     *
     * @return array{messages: \Illuminate\Support\Collection, has_more: bool}
     */
    public function getMessages(User $user, Conversation $conversation, ?int $beforeId = null, int $perPage = 30): array
    {
        $this->conversationService->ensureUserIsParticipant($user, $conversation);

        $rows = Message::withTrashed()
            ->where('conversation_id', $conversation->id)
            ->when($beforeId, fn ($q) => $q->where('id', '<', $beforeId))
            ->with(['sender', 'replyTo', 'attachments', 'reactions'])
            ->orderByDesc('id')
            ->limit($perPage + 1)
            ->get();

        return [
            'messages' => $rows->take($perPage)->values(),
            'has_more' => $rows->count() > $perPage,
        ];
    }

    /** Only the author may edit, and only text messages. */
    public function edit(User $user, Message $message, string $body): Message
    {
        $this->ensureAuthor($user, $message);

        if ($message->type !== 'text') {
            throw ValidationException::withMessages(['body' => ['Hanya pesan teks yang bisa diedit.']]);
        }

        $message->update(['body' => $body, 'edited_at' => now()]);

        $this->announce($message, 'edited');

        return $message->load(['sender', 'replyTo', 'attachments', 'reactions']);
    }

    /** Soft delete: the row stays so replies keep working, but the body is no longer served. */
    public function delete(User $user, Message $message): Message
    {
        $this->ensureAuthor($user, $message);

        $message->delete();

        $this->announce($message, 'deleted');

        return $message->load(['sender', 'replyTo', 'attachments', 'reactions']);
    }

    /** Add the reaction, or remove it if this user already reacted with the same emoji. */
    public function toggleReaction(User $user, Message $message, string $emoji): Message
    {
        $this->conversationService->ensureUserIsParticipant($user, $message->conversation);

        $existing = $message->reactions()->where('user_id', $user->id)->where('emoji', $emoji)->first();

        $existing
            ? $existing->delete()
            : $message->reactions()->create(['user_id' => $user->id, 'emoji' => $emoji]);

        $this->announce($message, 'reacted');

        return $message->load(['sender', 'replyTo', 'attachments', 'reactions']);
    }

    private function ensureAuthor(User $user, Message $message): void
    {
        if ($message->sender_id !== $user->id) {
            throw ValidationException::withMessages(['message' => ['Kamu hanya bisa mengubah pesanmu sendiri.']]);
        }
    }

    private function announce(Message $message, string $action): void
    {
        try {
            broadcast(new MessageChanged($message, $action))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function markAsRead(User $user, Conversation $conversation): void
    {
        $this->conversationService->ensureUserIsParticipant($user, $conversation);

        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);
    }
}