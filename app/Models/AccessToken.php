<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sanctum stores "last used" on every authenticated request, which here is a database write across the
 * network each time. Writing it at most once every 10 minutes is plenty for a "last used" label.
 */
class AccessToken extends PersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    public function save(array $options = [])
    {
        if ($this->exists && array_keys($this->getDirty()) === ['last_used_at']) {
            $previous = $this->getOriginal('last_used_at');

            if ($previous && \Illuminate\Support\Carbon::parse($previous)->gt(now()->subMinutes(10))) {
                return true;
            }
        }

        return parent::save($options);
    }
}
