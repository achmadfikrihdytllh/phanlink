<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'theme',
        'density',
        'desktop_notifications',
        'message_previews',
        'who_can_message',
        'read_receipts',
        'show_online_status',
    ];

    protected function casts(): array
    {
        return [
            'desktop_notifications' => 'boolean',
            'message_previews' => 'boolean',
            'read_receipts' => 'boolean',
            'show_online_status' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
