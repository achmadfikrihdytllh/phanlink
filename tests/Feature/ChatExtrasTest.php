<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatExtrasTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    private User $other;

    private Conversation $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create(['username' => 'joker']);
        $this->other = User::factory()->create(['username' => 'skull']);

        $this->group = Conversation::create(['type' => 'group', 'name' => 'Thieves', 'created_by' => $this->me->id]);
        $this->group->participants()->attach([
            $this->me->id => ['role' => 'admin', 'joined_at' => now()],
            $this->other->id => ['role' => 'member', 'joined_at' => now()],
        ]);

        Sanctum::actingAs($this->me);
    }

    private function say(string $body, ?User $by = null): Message
    {
        return Message::create([
            'conversation_id' => $this->group->id,
            'sender_id' => ($by ?? $this->me)->id,
            'type' => 'text',
            'body' => $body,
        ]);
    }

    public function test_messages_load_in_pages_with_a_cursor(): void
    {
        foreach (range(1, 35) as $i) {
            $this->say("m{$i}");
        }

        $first = $this->getJson("/api/conversations/{$this->group->id}/messages")
            ->assertOk()
            ->assertJsonCount(30, 'messages')
            ->assertJsonPath('meta.has_more', true);

        $oldest = $first->json('meta.oldest_id');

        $this->getJson("/api/conversations/{$this->group->id}/messages?before={$oldest}")
            ->assertOk()
            ->assertJsonCount(5, 'messages')
            ->assertJsonPath('meta.has_more', false);
    }

    public function test_reply_must_stay_in_the_same_conversation(): void
    {
        $elsewhere = Conversation::create(['type' => 'group', 'name' => 'Other', 'created_by' => $this->me->id]);
        $elsewhere->participants()->attach([$this->me->id => ['role' => 'admin', 'joined_at' => now()]]);
        $foreign = Message::create(['conversation_id' => $elsewhere->id, 'sender_id' => $this->me->id, 'type' => 'text', 'body' => 'x']);
        $mine = $this->say('hello');

        $this->postJson("/api/conversations/{$this->group->id}/messages", ['type' => 'text', 'body' => 'hi', 'reply_to_id' => $foreign->id])
            ->assertStatus(422);

        $this->postJson("/api/conversations/{$this->group->id}/messages", ['type' => 'text', 'body' => 'hi', 'reply_to_id' => $mine->id])
            ->assertCreated()
            ->assertJsonPath('data.reply_to.id', $mine->id);
    }

    public function test_author_can_edit_and_delete_but_others_cannot(): void
    {
        $mine = $this->say('typo');
        $theirs = $this->say('not yours', $this->other);

        $this->patchJson("/api/messages/{$mine->id}", ['body' => 'fixed'])
            ->assertOk()
            ->assertJsonPath('data.body', 'fixed');
        $this->assertNotNull($mine->fresh()->edited_at);

        $this->patchJson("/api/messages/{$theirs->id}", ['body' => 'hack'])->assertStatus(422);
        $this->deleteJson("/api/messages/{$theirs->id}")->assertStatus(422);

        $this->deleteJson("/api/messages/{$mine->id}")
            ->assertOk()
            ->assertJsonPath('data.deleted', true)
            ->assertJsonPath('data.body', null);

        // The thread still lists it as deleted, so replies to it keep working.
        $this->getJson("/api/conversations/{$this->group->id}/messages")
            ->assertJsonFragment(['id' => $mine->id, 'deleted' => true]);
    }

    public function test_reaction_toggles_on_and_off(): void
    {
        $m = $this->say('react to me', $this->other);

        $this->postJson("/api/messages/{$m->id}/reactions", ['emoji' => '👍'])
            ->assertOk()
            ->assertJsonPath('data.reactions.0.emoji', '👍')
            ->assertJsonPath('data.reactions.0.user_ids.0', $this->me->id);

        $this->postJson("/api/messages/{$m->id}/reactions", ['emoji' => '👍'])
            ->assertOk()
            ->assertJsonCount(0, 'data.reactions');
    }

    public function test_only_admins_pin_in_groups(): void
    {
        $m = $this->say('Leblanc, 17:00');

        $this->postJson("/api/conversations/{$this->group->id}/pin", ['message_id' => $m->id])
            ->assertOk()
            ->assertJsonPath('conversation.pinned_message.id', $m->id);

        $this->postJson("/api/conversations/{$this->group->id}/pin", ['message_id' => null])
            ->assertOk()
            ->assertJsonPath('conversation.pinned_message', null);

        Sanctum::actingAs($this->other);
        $this->postJson("/api/conversations/{$this->group->id}/pin", ['message_id' => $m->id])->assertStatus(422);
    }

    public function test_group_can_be_renamed_by_admin_only_and_members_can_leave(): void
    {
        $this->patchJson("/api/conversations/{$this->group->id}", ['name' => 'Crew'])
            ->assertOk()
            ->assertJsonPath('conversation.name', 'Crew');

        Sanctum::actingAs($this->other);
        $this->patchJson("/api/conversations/{$this->group->id}", ['name' => 'Hijack'])->assertStatus(422);
        $this->deleteJson("/api/conversations/{$this->group->id}/participants/{$this->me->id}")->assertStatus(422);

        // Leaving yourself is always allowed.
        $this->deleteJson("/api/conversations/{$this->group->id}/participants/{$this->other->id}")->assertOk();
        $this->assertFalse($this->group->participants()->where('user_id', $this->other->id)->exists());
    }

    public function test_last_admin_leaving_promotes_a_member(): void
    {
        $this->deleteJson("/api/conversations/{$this->group->id}/participants/{$this->me->id}")->assertOk();

        $this->assertSame(
            'admin',
            $this->group->participants()->where('user_id', $this->other->id)->first()->pivot->role,
        );
    }

    public function test_events_support_rsvp_and_announce_in_chat(): void
    {
        $res = $this->postJson("/api/conversations/{$this->group->id}/events", [
            'title' => 'Heist planning',
            'location' => 'Leblanc',
            'starts_at' => now()->addDays(2)->toIso8601String(),
        ])->assertCreated()
            ->assertJsonPath('event.counts.going', 1)
            ->assertJsonPath('event.my_response', 'going');

        $this->assertSame(1, Message::where('conversation_id', $this->group->id)->count());

        $id = $res->json('event.id');

        Sanctum::actingAs($this->other);
        $this->postJson("/api/events/{$id}/respond", ['status' => 'maybe'])
            ->assertOk()
            ->assertJsonPath('event.counts.maybe', 1)
            ->assertJsonPath('event.my_response', 'maybe');
        $this->postJson("/api/events/{$id}/respond", ['status' => 'nope'])->assertStatus(422);

        // A non-creator, non-admin cannot delete it; the creator can.
        $this->deleteJson("/api/events/{$id}")->assertStatus(422);
        Sanctum::actingAs($this->me);
        $this->deleteJson("/api/events/{$id}")->assertOk();
        $this->getJson("/api/conversations/{$this->group->id}/events")->assertJsonCount(0, 'events');
    }

    public function test_events_must_be_in_the_future_and_only_for_groups(): void
    {
        $this->postJson("/api/conversations/{$this->group->id}/events", ['title' => 'Past', 'starts_at' => now()->subDay()->toIso8601String()])
            ->assertStatus(422);

        $private = Conversation::create(['type' => 'private', 'created_by' => $this->me->id]);
        $private->participants()->attach([$this->me->id => ['role' => 'member', 'joined_at' => now()], $this->other->id => ['role' => 'member', 'joined_at' => now()]]);

        $this->postJson("/api/conversations/{$private->id}/events", ['title' => 'Nope', 'starts_at' => now()->addDay()->toIso8601String()])
            ->assertStatus(422);
    }

    public function test_polls_one_vote_per_person_and_vote_can_move(): void
    {
        $res = $this->postJson("/api/conversations/{$this->group->id}/polls", [
            'question' => 'Sabtu atau Minggu?',
            'options' => ['Sabtu', 'Minggu'],
        ])->assertCreated();

        $pollId = $res->json('poll.id');
        [$a, $b] = [$res->json('poll.options.0.id'), $res->json('poll.options.1.id')];

        $this->postJson("/api/polls/{$pollId}/vote", ['option_id' => $a])
            ->assertJsonPath('poll.total_votes', 1)
            ->assertJsonPath('poll.my_vote', $a);

        $this->postJson("/api/polls/{$pollId}/vote", ['option_id' => $b])
            ->assertJsonPath('poll.total_votes', 1)
            ->assertJsonPath('poll.options.0.votes', 0)
            ->assertJsonPath('poll.options.1.votes', 1);

        $this->postJson("/api/polls/{$pollId}/vote", ['option_id' => 999999])->assertStatus(422);

        $this->postJson("/api/conversations/{$this->group->id}/polls", ['question' => 'x', 'options' => ['only one']])->assertStatus(422);
    }

    public function test_who_can_message_is_enforced_when_starting_a_chat(): void
    {
        $stranger = User::factory()->create();

        // Default is "contacts only" and the stranger has not added me.
        $this->postJson('/api/conversations/private', ['user_id' => $stranger->id])->assertStatus(422);

        Contact::create(['user_id' => $stranger->id, 'contact_id' => $this->me->id]);
        $this->postJson('/api/conversations/private', ['user_id' => $stranger->id])->assertOk();

        $open = User::factory()->create();
        UserSetting::create(['user_id' => $open->id, 'who_can_message' => 'everyone']);
        $this->postJson('/api/conversations/private', ['user_id' => $open->id])->assertOk();
    }

    public function test_hidden_online_status_and_read_receipts_are_not_exposed(): void
    {
        $this->other->forceFill(['status' => 'online'])->save();
        UserSetting::create(['user_id' => $this->other->id, 'show_online_status' => false, 'read_receipts' => false]);
        $this->group->participants()->updateExistingPivot($this->other->id, ['last_read_at' => now()]);

        $this->getJson("/api/conversations/{$this->group->id}")
            ->assertOk()
            ->assertJsonPath('conversation.participants.1.status', 'offline')
            ->assertJsonPath('conversation.participants.1.last_read_at', null);
    }

    public function test_password_reset_flow(): void
    {
        $user = User::factory()->create(['email' => 'reset@phanlink.test']);

        $this->postJson('/api/forgot-password', ['email' => 'reset@phanlink.test'])->assertOk();
        // Unknown emails get the same answer.
        $this->postJson('/api/forgot-password', ['email' => 'nobody@phanlink.test'])->assertOk();

        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token, 'email' => 'reset@phanlink.test',
            'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertOk();

        $this->postJson('/api/login', ['login' => 'reset@phanlink.test', 'password' => 'brand-new-pass'])->assertOk();

        $this->postJson('/api/reset-password', [
            'token' => 'wrong', 'email' => 'reset@phanlink.test',
            'password' => 'another-pass-1', 'password_confirmation' => 'another-pass-1',
        ])->assertStatus(422);
    }
}
