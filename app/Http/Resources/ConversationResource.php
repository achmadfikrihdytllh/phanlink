<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authUserId = $request->user()->id;

        // Untuk private chat, "nama" & "avatar" yang ditampilkan
        // diambil dari lawan bicara, bukan dari tabel conversations
        $displayName = $this->name;
        $displayAvatar = $this->avatar;

        if ($this->type === 'private') {
            $otherParticipant = $this->participants->firstWhere('id', '!=', $authUserId);
            $displayName = $otherParticipant?->name;
            $displayAvatar = $otherParticipant?->avatar;
        }

        // Pivot row of the signed-in user (read marker, mute/pin preferences)
        $mine = $this->participants->firstWhere('id', $authUserId)?->pivot;

        $unread = $this->messages()
            ->where('sender_id', '!=', $authUserId)
            ->when($mine?->last_read_at, fn ($q, $readAt) => $q->where('created_at', '>', $readAt))
            ->count();

        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $displayName,
            'avatar' => $displayAvatar,
            'description' => $this->description,
            'created_by' => $this->created_by,
            'unread_count' => $unread,
            'pinned_message' => $this->pinned_message_id && $this->pinnedMessage ? [
                'id' => $this->pinnedMessage->id,
                'body' => $this->pinnedMessage->body,
                'sender_id' => $this->pinnedMessage->sender_id,
            ] : null,
            'is_muted' => (bool) ($mine?->is_muted ?? false),
            'is_pinned' => (bool) ($mine?->is_pinned ?? false),
            'participants' => $this->whenLoaded('participants', function () use ($authUserId) {
                return $this->participants->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                    'status' => $user->publicStatus($authUserId),
                    'role' => $user->pivot->role,
                    // Read marker for the "seen" ticks. Hidden when that person turned read receipts off.
                    'last_read_at' => ($user->id === $authUserId || ($user->settings?->read_receipts ?? true))
                        ? $user->pivot->last_read_at
                        : null,
                ]);
            }),
            'latest_message' => $this->whenLoaded('latestMessage', function () {
                return $this->latestMessage ? [
                    'id' => $this->latestMessage->id,
                    'body' => $this->latestMessage->body,
                    'type' => $this->latestMessage->type,
                    'sender_id' => $this->latestMessage->sender_id,
                    'created_at' => $this->latestMessage->created_at,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}