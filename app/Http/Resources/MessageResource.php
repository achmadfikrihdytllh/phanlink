<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'username' => $this->sender->username,
                'avatar' => $this->sender->avatar,
            ],
            'type' => $this->type,
            'body' => $this->trashed() ? null : $this->body,
            'deleted' => $this->trashed(),
            'reply_to' => $this->whenLoaded('replyTo', function () {
                return $this->replyTo ? [
                    'id' => $this->replyTo->id,
                    'body' => $this->replyTo->trashed() ? null : $this->replyTo->body,
                    'sender_id' => $this->replyTo->sender_id,
                ] : null;
            }),
            'reactions' => $this->whenLoaded('reactions', function () {
                return $this->reactions->groupBy('emoji')->map(fn ($rows, $emoji) => [
                    'emoji' => $emoji,
                    'user_ids' => $rows->pluck('user_id')->values(),
                ])->values();
            }),
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'file_path' => $a->file_path,
                    'file_type' => $a->file_type,
                    'file_size' => $a->file_size,
                    'thumbnail_path' => $a->thumbnail_path,
                ]);
            }),
            'edited_at' => $this->edited_at,
            'created_at' => $this->created_at,
        ];
    }
}