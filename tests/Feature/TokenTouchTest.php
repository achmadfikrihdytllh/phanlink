<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenTouchTest extends TestCase
{
    use RefreshDatabase;

    private function ping(string $plain): void
    {
        $this->flushHeaders()
            ->withHeader('Authorization', "Bearer $plain")
            ->getJson('/api/conversations')
            ->assertOk();
    }

    public function test_token_last_used_is_not_written_on_every_request(): void
    {
        $token = User::factory()->create()->createToken('t');
        $row = $token->accessToken;

        // Used a minute ago: another request must not write it again.
        $row->forceFill(['last_used_at' => now()->subMinute()])->saveQuietly();
        $before = $row->fresh()->last_used_at;

        $this->ping($token->plainTextToken);

        $this->assertEquals($before, $row->fresh()->last_used_at);
    }

    public function test_token_last_used_is_refreshed_after_ten_minutes(): void
    {
        $token = User::factory()->create()->createToken('t');
        $row = $token->accessToken;

        $row->forceFill(['last_used_at' => now()->subHour()])->saveQuietly();

        $this->ping($token->plainTextToken);

        $this->assertTrue($row->fresh()->last_used_at->gt(now()->subMinute()));
    }

    public function test_a_first_request_with_a_new_token_still_stamps_it(): void
    {
        $token = User::factory()->create()->createToken('t');

        $this->ping($token->plainTextToken);

        $this->assertNotNull($token->accessToken->fresh()->last_used_at);
    }
}
