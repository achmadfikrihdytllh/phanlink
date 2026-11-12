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

            // Hapus avatar lama kalau ada, biar storage nggak numpuk file nggak kepakai
            if ($user->avatar) {
                $prefix = rtrim($disk->url(''), '/') . '/';
                $oldPath = str_starts_with($user->avatar, $prefix)
                    ? substr($user->avatar, strlen($prefix))
                    : ltrim(str_replace('/storage/', '', $user->avatar), '/');
                $disk->delete($oldPath);
            }

            $path = $data['avatar']->store('avatars', config('filesystems.uploads'));
            abort_if(! $path, 500, 'Foto gagal diunggah ke penyimpanan.');
            $data['avatar'] = $disk->url($path);
        }

        $user->update($data);

        return $user->fresh();
    }
}