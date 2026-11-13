<?php

namespace App\Support;

class Mentions
{
    /**
     * True when the text tags this username as "@username" (at the start or after whitespace).
     * Same rule as the app's message view: a trailing dot belongs to the sentence ("hi @joker.").
     */
    public static function tags(?string $body, ?string $username): bool
    {
        if (! $body || ! $username || ! str_contains($body, '@')) {
            return false;
        }

        if (! preg_match_all('/(?:^|\s)@([\w.]{1,30})/u', $body, $found)) {
            return false;
        }

        foreach ($found[1] as $raw) {
            if (strcasecmp(rtrim($raw, '.'), $username) === 0) {
                return true;
            }
        }

        return false;
    }
}
