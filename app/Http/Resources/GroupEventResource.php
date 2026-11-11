<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $uid = $request->user()->id;
        $counts = $this->responses->countBy('status');

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'starts_at' => $this->starts_at,
            'created_by' => ['id' => $this->creator->id, 'name' => $this->creator->name],
            'counts' => [
                'going' => $counts['going'] ?? 0,
                'maybe' => $counts['maybe'] ?? 0,
                'declined' => $counts['declined'] ?? 0,
            ],
            'my_response' => $this->responses->firstWhere('user_id', $uid)?->status,
        ];
    }
}
