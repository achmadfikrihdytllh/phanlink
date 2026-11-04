<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $message = $this->message;
        $conversation = $message->conversation;

        $groupName = $conversation->type === 'private'
            ? $conversation->participants->firstWhere('id', '!=', $request->user()->id)?->name
            : $conversation->name;

        return [
            'id' => $this->id,
            'saved_at' => $this->created_at,
            'collection' => $this->collection
                ? ['id' => $this->collection->id, 'name' => $this->collection->name]
                : null,
            'conversation' => [
                'id' => $conversation->id,
                'name' => $groupName,
                'type' => $conversation->type,
            ],
            'message' => new MessageResource($message),
        ];
    }
}
