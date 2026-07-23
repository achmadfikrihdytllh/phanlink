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

        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $displayName,
            'avatar' => $displayAvatar,
            'created_by' => $this->created_by,
            'participants' => $this->whenLoaded('participants', function () {
                return $this->participants->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                    'status' => $user->status,
                    'role' => $user->pivot->role,
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