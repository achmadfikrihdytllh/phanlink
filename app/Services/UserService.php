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
            $disk = Storage::disk(config('filesystems.uploads'));

            $old = $user->avatar;

            $path = $data['avatar']->store('avatars', config('filesystems.uploads'));
            abort_if(! $path, 500, 'Foto gagal diunggah ke penyimpanan.');
            $data['avatar'] = $disk->url($path);

            // Hapus avatar lama (best effort): kegagalan hapus tidak boleh menggagalkan upload baru.
            if ($old && preg_match('#(avatars/[^/?]+)$#', $old, $m) && $m[1] !== $path) {
                try {
                    $disk->delete($m[1]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $user->update($data);

        return $user->fresh();
    }
}