<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'phone_number' => $this->phone_number,
            'status' => $this->publicStatus($request->user()?->id),
            'last_seen_at' => $this->publicLastSeen($request->user()?->id),
        ];
    }
}