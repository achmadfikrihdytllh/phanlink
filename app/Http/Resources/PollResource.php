<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $uid = $request->user()->id;
        $myVote = $this->votes->firstWhere('user_id', $uid)?->poll_option_id;

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'question' => $this->question,
            'created_by' => $this->created_by,
            'total_votes' => $this->votes->count(),
            'my_vote' => $myVote,
            'options' => $this->options->map(fn ($o) => [
                'id' => $o->id,
                'label' => $o->label,
                'votes' => $this->votes->where('poll_option_id', $o->id)->count(),
            ])->values(),
            'created_at' => $this->created_at,
        ];
    }
}
