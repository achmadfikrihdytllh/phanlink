<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhantomApiTest extends TestCase
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

    private function message(string $body, ?User $sender = null): Message
    {
        return Message::create([
            'conversation_id' => $this->group->id,
            'sender_id' => ($sender ?? $this->other)->id,
            'type' => 'text',
            'body' => $body,
        ]);
    }

    public function test_conversations_include_unread_count_and_preferences(): void
    {
        $this->message('hello');
        $this->message('meet at Leblanc');

        $this->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonPath('conversations.0.unread_count', 2)
            ->assertJsonPath('conversations.0.is_pinned', false);

        $this->patchJson("/api/conversations/{$this->group->id}/preferences", ['is_pinned' => true, 'is_muted' => true])
            ->assertOk()
            ->assertJsonPath('conversation.is_pinned', true)
            ->assertJsonPath('conversation.is_muted', true);
    }

    public function test_groups_endpoint_lists_only_groups(): void
    {
        $this->getJson('/api/groups')
            ->assertOk()
            ->assertJsonCount(1, 'groups')
            ->assertJsonPath('groups.0.name', 'Thieves');
    }

    public function test_only_group_admins_can_invite(): void
    {
        $newbie = User::factory()->create();

        Sanctum::actingAs($this->other);
        $this->postJson("/api/conversations/{$this->group->id}/participants", ['user_ids' => [$newbie->id]])
            ->assertStatus(422);

        Sanctum::actingAs($this->me);
        $this->postJson("/api/conversations/{$this->group->id}/participants", ['user_ids' => [$newbie->id]])
            ->assertOk()
            ->assertJsonCount(3, 'conversation.participants');
    }

    public function test_contacts_crud_and_profile(): void
    {
        $this->postJson('/api/contacts', ['user_id' => $this->other->id, 'nickname' => 'Skull'])
            ->assertCreated()
            ->assertJsonPath('contact.codename', 'Skull')
            ->assertJsonPath('contact.handle', '@skull');

        $this->postJson('/api/contacts', ['user_id' => $this->me->id])->assertStatus(422);

        $this->message('ramen?');

        $this->getJson("/api/contacts/{$this->other->id}")
            ->assertOk()
            ->assertJsonCount(1, 'groups_in_common');

        $this->patchJson("/api/contacts/{$this->other->id}", ['note' => 'ask about ramen', 'is_muted' => true])
            ->assertOk()
            ->assertJsonPath('contact.note', 'ask about ramen')
            ->assertJsonPath('contact.is_muted', true);

        $this->deleteJson("/api/contacts/{$this->other->id}")->assertOk();
        $this->getJson('/api/contacts')->assertOk()->assertJsonCount(0, 'contacts');
    }

    public function test_saved_messages_and_collections(): void
    {
        $message = $this->message('game plan');

        $collection = $this->postJson('/api/saved/collections', ['name' => 'Plans'])
            ->assertCreated()
            ->json('collection');

        $this->postJson('/api/saved/collections', ['name' => 'Plans'])->assertStatus(422);

        $this->postJson('/api/saved', ['message_id' => $message->id, 'collection_id' => $collection['id']])
            ->assertCreated()
            ->assertJsonPath('saved.collection.name', 'Plans');

        $this->getJson('/api/saved')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('collections.0.count', 1)
            ->assertJsonPath('saved.0.message.body', 'game plan');

        $this->getJson('/api/saved?q=nothing-matches')->assertOk()->assertJsonCount(0, 'saved');
    }

    public function test_cannot_save_message_from_a_conversation_you_are_not_in(): void
    {
        $stranger = User::factory()->create();
        $secret = Conversation::create(['type' => 'group', 'name' => 'Secret', 'created_by' => $stranger->id]);
        $secret->participants()->attach([$stranger->id => ['role' => 'admin', 'joined_at' => now()]]);

        $message = Message::create([
            'conversation_id' => $secret->id,
            'sender_id' => $stranger->id,
            'type' => 'text',
            'body' => 'private',
        ]);

        $this->postJson('/api/saved', ['message_id' => $message->id])->assertUnprocessable();
    }

    public function test_search_finds_messages_only_in_my_conversations(): void
    {
        $this->message('Meet at Leblanc');
        $this->message('something else');

        $stranger = User::factory()->create();
        $secret = Conversation::create(['type' => 'group', 'name' => 'Secret', 'created_by' => $stranger->id]);
        $secret->participants()->attach([$stranger->id => ['role' => 'admin', 'joined_at' => now()]]);
        Message::create(['conversation_id' => $secret->id, 'sender_id' => $stranger->id, 'type' => 'text', 'body' => 'Leblanc secret']);

        $this->getJson('/api/search?q=leblanc')
            ->assertOk()
            ->assertJsonPath('counts.messages', 1)
            ->assertJsonPath('messages.0.conversation.name', 'Thieves');

        $this->getJson('/api/search')->assertStatus(422);
    }

    public function test_settings_round_trip_with_camel_case_keys(): void
    {
        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('settings.theme', 'phantom')
            ->assertJsonPath('settings.readReceipts', true);

        $this->putJson('/api/settings', ['theme' => 'paper', 'readReceipts' => false, 'whoCanMessage' => 'everyone'])
            ->assertOk()
            ->assertJsonPath('settings.theme', 'paper')
            ->assertJsonPath('settings.readReceipts', false);

        $this->putJson('/api/settings', ['theme' => 'neon'])->assertStatus(422);
    }

    public function test_login_and_logout_update_presence(): void
    {
        $user = User::factory()->create(['username' => 'ann', 'password' => bcrypt('secret123')]);

        $this->postJson('/api/login', ['login' => 'ann', 'password' => 'secret123'])->assertOk();
        $this->assertSame('online', $user->fresh()->status);
    }
}
