<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'status',
        'bio',
        'phone_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_seen_at' => 'datetime',
        ];
    }

    // Semua percakapan yang diikuti user ini
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['role', 'last_read_at', 'is_muted', 'is_pinned', 'joined_at'])
            ->withTimestamps();
    }

    // Kontak ("confidants") milik user ini
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function savedCollections(): HasMany
    {
        return $this->hasMany(SavedCollection::class);
    }

    public function savedMessages(): HasMany
    {
        return $this->hasMany(SavedMessage::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    // Semua pesan yang dikirim user ini
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
}