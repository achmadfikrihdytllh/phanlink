<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
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

        return DB::transaction(function () use ($user, $conversation, $data) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'type' => $data['type'],
                'body' => $data['body'] ?? null,
                'reply_to_id' => $data['reply_to_id'] ?? null,
            ]);

            if (isset($data['attachment'])) {
                $file = $data['attachment'];
                $path = $file->store('attachments/' . $conversation->id, 'public');

                $message->attachments()->create([
                    'file_path' => Storage::url($path),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }

            // Pengirim otomatis dianggap sudah membaca pesannya sendiri
            $conversation->participants()->updateExistingPivot($user->id, [
                'last_read_at' => now(),
            ]);

            return $message->load(['sender', 'replyTo', 'attachments']);
        });
    }

    public function getMessages(User $user, Conversation $conversation, int $perPage = 30): LengthAwarePaginator
    {
        $this->conversationService->ensureUserIsParticipant($user, $conversation);

        return Message::where('conversation_id', $conversation->id)
            ->with(['sender', 'replyTo', 'attachments'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function markAsRead(User $user, Conversation $conversation): void
    {
        $this->conversationService->ensureUserIsParticipant($user, $conversation);

        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);
    }
}