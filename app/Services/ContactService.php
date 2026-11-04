<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\MessageAttachment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ContactService
{
    public function __construct(protected ConversationService $conversationService)
    {
    }

    public function list(User $user): Collection
    {
        return $user->contacts()->with('contact')->get()
            ->sortBy(fn (Contact $c) => mb_strtolower($c->contact->name))
            ->values();
    }

    public function add(User $user, int $contactUserId, ?string $nickname = null): Contact
    {
        if ($contactUserId === $user->id) {
            throw ValidationException::withMessages([
                'user_id' => ['Kamu tidak bisa menambahkan dirimu sendiri.'],
            ]);
        }

        $contact = Contact::firstOrCreate(
            ['user_id' => $user->id, 'contact_id' => $contactUserId],
            ['nickname' => $nickname],
        );

        return $contact->load('contact');
    }

    /** Find one of the user's contacts by the contact's *user* id. */
    public function find(User $user, int $contactUserId): Contact
    {
        return $user->contacts()->with('contact')
            ->where('contact_id', $contactUserId)
            ->firstOrFail();
    }

    public function update(Contact $contact, array $data): Contact
    {
        $contact->update($data);

        return $contact->load('contact');
    }

    public function remove(Contact $contact): void
    {
        $contact->delete();
    }

    /**
     * Extra data for the contact profile page: shared groups, shared files and
     * the most recent messages in the direct conversation.
     */
    public function detail(User $user, Contact $contact): array
    {
        $other = $contact->contact;

        $private = $this->conversationService->findPrivate($user, $other->id);
        $groups = $this->conversationService->groupsInCommon($user, $other->id);

        $conversationIds = $groups->pluck('id')
            ->when($private, fn ($ids) => $ids->push($private->id))
            ->all();

        $files = $conversationIds === [] ? collect() : MessageAttachment::query()
            ->whereHas('message', fn ($q) => $q->whereIn('conversation_id', $conversationIds))
            ->with('message.sender')
            ->latest()
            ->limit(10)
            ->get();

        $exchange = $private === null ? collect() : Message::query()
            ->where('conversation_id', $private->id)
            ->with('sender')
            ->latest()
            ->limit(2)
            ->get()
            ->reverse()
            ->values();

        return [
            'conversation_id' => $private?->id,
            'groups_in_common' => $groups,
            'shared_files' => $files,
            'last_exchange' => $exchange,
        ];
    }
}
