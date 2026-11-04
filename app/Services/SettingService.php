<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSetting;

class SettingService
{
    /** Settings are created lazily with column defaults the first time they are read. */
    public function get(User $user): UserSetting
    {
        return $user->settings()->firstOrCreate([])->refresh();
    }

    public function update(User $user, array $data): UserSetting
    {
        $settings = $this->get($user);
        $settings->update($data);

        return $settings->refresh();
    }
}
