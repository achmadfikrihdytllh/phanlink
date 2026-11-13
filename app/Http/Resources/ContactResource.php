<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A row of the signed-in user's contact list. `id` is the *user* id of the contact. */
class ContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->contact;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'handle' => '@'.$user->username,
            'codename' => $this->nickname ?: $user->username,
            'avatar' => $user->avatar,
            'bio' => $user->bio,
            'status' => $user->publicStatus($request->user()?->id),
            'last_seen_at' => $user->publicLastSeen($request->user()?->id),
            'note' => $this->note,
            'is_muted' => $this->is_muted,
            'is_blocked' => $this->is_blocked,
            'contact_since' => $this->created_at,
        ];
    }
}
