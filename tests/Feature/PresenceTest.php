<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PresenceTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    private User $other;

    private Conversation $chat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create();
        $this->other = User::factory()->create();

        $this->chat = Conversation::create(['type' => 'private', 'created_by' => $this->me->id]);
        $this->chat->participants()->attach([
            $this->me->id => ['role' => 'member', 'joined_at' => now()],
            $this->other->id => ['role' => 'member', 'joined_at' => now()],
        ]);

        Sanctum::actingAs($this->me);
    }

    private function otherInChat(): array
    {
        return $this->getJson("/api/conversations/{$this->chat->id}")
            ->assertOk()
            ->json('conversation.participants.1');
    }

    public function test_any_authenticated_request_refreshes_last_seen(): void
    {
        $this->me->forceFill(['status' => 'offline', 'last_seen_at' => now()->subHour()])->save();

        $this->getJson('/api/conversations')->assertOk();

        $fresh = $this->me->fresh();
        $this->assertSame('online', $fresh->status);
        $this->assertTrue($fresh->last_seen_at->gt(now()->subMinute()));
    }

    public function test_recent_activity_shows_online_with_a_timestamp(): void
    {
        $this->other->forceFill(['status' => 'online', 'last_seen_at' => now()->subSeconds(20)])->save();

        $row = $this->otherInChat();

        $this->assertSame('online', $row['status']);
        $this->assertNotNull($row['last_seen_at']);
    }

    public function test_someone_who_went_quiet_is_offline_but_keeps_last_seen(): void
    {
        // Closed the tab without logging out: status is still "online" in the database.
        $this->other->forceFill(['status' => 'online', 'last_seen_at' => now()->subMinutes(10)])->save();

        $row = $this->otherInChat();

        $this->assertSame('offline', $row['status']);
        $this->assertNotNull($row['last_seen_at']);
    }

    public function test_last_seen_is_hidden_when_online_status_is_off(): void
    {
        $this->other->forceFill(['status' => 'online', 'last_seen_at' => now()])->save();
        UserSetting::create(['user_id' => $this->other->id, 'show_online_status' => false]);

        $row = $this->otherInChat();

        $this->assertSame('offline', $row['status']);
        $this->assertNull($row['last_seen_at']);
    }
}
