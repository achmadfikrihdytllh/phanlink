<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Support\Collection;

class SearchService
{
    /** How many recent rows are scanned in PHP, because bodies are encrypted at rest. */
    private const SCAN_LIMIT = 1000;

    private const RESULT_LIMIT = 50;

    /**
     * @param  array{conversation_id?: int|null, from?: int|null, date_from?: string|null, date_to?: string|null, types?: array<int,string>|null}  $filters
     * @return array{messages: Collection<int, Message>, files: Collection<int, MessageAttachment>}
     */
    public function search(User $user, string $query, array $filters = []): array
    {
        $types = $filters['types'] ?? ['messages', 'files'];

        $conversationIds = $user->conversations()->pluck('conversations.id');

        if (! empty($filters['conversation_id'])) {
            $conversationIds = $conversationIds->intersect([(int) $filters['conversation_id']])->values();
        }

        if ($conversationIds->isEmpty()) {
            return ['messages' => collect(), 'files' => collect()];
        }

        $scope = function ($q) use ($conversationIds, $filters) {
            $q->whereIn('conversation_id', $conversationIds)
                ->when($filters['from'] ?? null, fn ($q, $id) => $q->where('sender_id', $id))
                ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
        };

        $messages = collect();
        if (in_array('messages', $types, true)) {
            $messages = Message::query()
                ->tap($scope)
                ->with(['sender', 'conversation.participants'])
                ->latest()
                ->limit(self::SCAN_LIMIT)
                ->get()
                ->filter(fn (Message $m) => $m->body !== null && mb_stripos($m->body, $query) !== false)
                ->take(self::RESULT_LIMIT)
                ->values();
        }

        $files = collect();
        if (in_array('files', $types, true)) {
            $files = MessageAttachment::query()
                ->whereHas('message', $scope)
                ->with(['message.sender', 'message.conversation.participants'])
                ->latest()
                ->limit(self::SCAN_LIMIT)
                ->get()
                ->filter(fn (MessageAttachment $a) => mb_stripos(basename($a->file_path), $query) !== false)
                ->take(self::RESULT_LIMIT)
                ->values();
        }

        return ['messages' => $messages, 'files' => $files];
    }
}
