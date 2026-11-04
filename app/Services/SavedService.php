<?php

namespace App\Services;

use App\Models\Message;
use App\Models\SavedCollection;
use App\Models\SavedMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SavedService
{
    public function __construct(protected ConversationService $conversationService)
    {
    }

    /**
     * @param  array{collection_id?: int|null, type?: string|null, q?: string|null}  $filters
     */
    public function list(User $user, array $filters = []): Collection
    {
        $saved = $user->savedMessages()
            ->with(['collection', 'message.sender', 'message.attachments', 'message.conversation.participants'])
            ->latest()
            ->get();

        $q = trim((string) ($filters['q'] ?? ''));

        return $saved
            ->when($filters['collection_id'] ?? null, fn ($c, $id) => $c->where('collection_id', (int) $id))
            ->when($filters['type'] ?? null, function ($c, $type) {
                return match ($type) {
                    'files' => $c->filter(fn (SavedMessage $s) => $s->message->attachments->isNotEmpty()),
                    'messages' => $c->filter(fn (SavedMessage $s) => $s->message->attachments->isEmpty()),
                    default => $c,
                };
            })
            // Message bodies are encrypted at rest, so text search has to happen in PHP.
            ->when($q !== '', fn ($c) => $c->filter(
                fn (SavedMessage $s) => mb_stripos((string) $s->message->body, $q) !== false
                    || mb_stripos($s->message->sender->name, $q) !== false
            ))
            ->values();
    }

    public function save(User $user, int $messageId, ?int $collectionId = null): SavedMessage
    {
        $message = Message::with('conversation')->findOrFail($messageId);

        // You can only save messages from conversations you are part of.
        $this->conversationService->ensureUserIsParticipant($user, $message->conversation);

        if ($collectionId !== null) {
            $this->ownedCollection($user, $collectionId);
        }

        $saved = SavedMessage::updateOrCreate(
            ['user_id' => $user->id, 'message_id' => $message->id],
            ['collection_id' => $collectionId],
        );

        return $saved->load(['collection', 'message.sender', 'message.attachments', 'message.conversation.participants']);
    }

    public function remove(User $user, int $savedId): void
    {
        $user->savedMessages()->findOrFail($savedId)->delete();
    }

    /** Collections with their item counts. */
    public function collections(User $user): Collection
    {
        return $user->savedCollections()->withCount('savedMessages')->orderBy('name')->get();
    }

    public function createCollection(User $user, string $name): SavedCollection
    {
        $name = trim($name);

        if ($user->savedCollections()->where('name', $name)->exists()) {
            throw ValidationException::withMessages([
                'name' => ['Koleksi dengan nama itu sudah ada.'],
            ]);
        }

        return $user->savedCollections()->create(['name' => $name]);
    }

    public function deleteCollection(User $user, int $collectionId): void
    {
        // Saved messages stay saved; they just become uncategorised (nullOnDelete).
        $this->ownedCollection($user, $collectionId)->delete();
    }

    public function total(User $user): int
    {
        return $user->savedMessages()->count();
    }

    private function ownedCollection(User $user, int $collectionId): SavedCollection
    {
        return $user->savedCollections()->findOrFail($collectionId);
    }
}
