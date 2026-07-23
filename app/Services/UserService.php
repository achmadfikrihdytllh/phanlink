<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function search(User $currentUser, string $query)
    {
        return User::where('id', '!=', $currentUser->id)
            ->where(function ($q) use ($query) {
                $q->where('username', 'like', "%{$query}%")
                    ->orWhere('email', $query); // email dicari exact match, bukan partial (lebih privat)
            })
            ->limit(20)
            ->get();
    }

    public function updateProfile(User $user, array $data): User
    {
        if (isset($data['avatar'])) {
            // Hapus avatar lama kalau ada, biar storage nggak numpuk file nggak kepakai
            if ($user->avatar) {
                $oldPath = str_replace('/storage/', '', $user->avatar);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $data['avatar']->store('avatars', 'public');
            $data['avatar'] = Storage::url($path);
        }

        $user->update($data);

        return $user->fresh();
    }
}