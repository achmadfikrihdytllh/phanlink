<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\SavedCollection;
use App\Models\SavedMessage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data that matches the Phantom Messenger mockup.
 * Log in with  joker / password  (or joker@phanlink.test).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $people = [
            'joker' => ['Ren Amamiya', 'Laying low', 'online'],
            'skull' => ['Ryuji Sakamoto', 'Track team energy. Phantom Thief loyalty.', 'online'],
            'panther' => ['Ann Takamaki', 'Model, thief, and fan of crepes.', 'online'],
            'queen' => ['Makoto Niijima', 'Student council president.', 'online'],
            'oracle' => ['Futaba Sakura', 'Hacker. Snack enthusiast.', 'offline'],
            'fox' => ['Yusuke Kitagawa', 'Artist. Hungry.', 'offline'],
            'mona' => ['Morgana', 'Definitely not a cat.', 'online'],
        ];

        $users = [];
        foreach ($people as $username => [$name, $bio, $status]) {
            $users[$username] = User::updateOrCreate(
                ['username' => $username],
                [
                    'name' => $name,
                    'email' => "{$username}@phanlink.test",
                    'password' => Hash::make('password'),
                    'bio' => $bio,
                    'status' => $status,
                ],
            );
        }

        $joker = $users['joker'];

        // Contacts of joker
        foreach ($users as $username => $user) {
            if ($user->id === $joker->id) {
                continue;
            }
            Contact::updateOrCreate(
                ['user_id' => $joker->id, 'contact_id' => $user->id],
                ['nickname' => ucfirst($username)],
            );
        }

        $thieves = $this->group('The Phantom Thieves', 'A place for the crew to plan our next move. No strangers. No spoilers. Don’t be late.', $joker, $users);
        $this->group('Leblanc regulars', 'Coffee, curry, and a place to lay low.', $joker, [$users['joker'], $users['mona'], $users['queen'], $users['panther']]);
        $this->group('Operation: snack run', 'Futaba: Operation is a go.', $joker, [$users['joker'], $users['oracle'], $users['skull']]);
        $this->group('After-school plans', 'See you after class!', $joker, [$users['joker'], $users['panther'], $users['skull'], $users['queen'], $users['fox']]);

        $direct = Conversation::create(['type' => 'private', 'created_by' => $joker->id]);
        $direct->participants()->attach([
            $joker->id => ['role' => 'member', 'joined_at' => now()],
            $users['skull']->id => ['role' => 'member', 'joined_at' => now()],
        ]);

        $today = now()->startOfDay();

        $this->say($thieves, $joker, 'Meet at Leblanc. 17:00. Don’t be late.', $today->copy()->setTime(16, 40));
        $m1 = $this->say($thieves, $users['panther'], 'So, Leblanc at five? I’m claiming the booth. We need a proper game plan for tomorrow.', $today->copy()->setTime(16, 42));
        $this->say($thieves, $joker, 'I’ll tell Sojiro we’re coming. Coffee’s on me.', $today->copy()->setTime(16, 42));
        $m2 = $this->say($thieves, $users['queen'], 'I’ll bring my notes. Let’s go over the route together before we make our next move.', $today->copy()->setTime(16, 43));
        $this->say($thieves, $users['skull'], 'Wait, does a game plan include curry?', $today->copy()->setTime(16, 44));
        $this->say($thieves, $joker, 'Only if you actually show up on time.', $today->copy()->setTime(16, 45));
        $this->say($thieves, $users['skull'], 'Okay, okay. I’ll be there!', $today->copy()->setTime(16, 46));

        $this->say($direct, $users['skull'], 'You up for ramen after this?', $today->copy()->setTime(16, 32));
        $this->say($direct, $joker, 'After Leblanc. Let’s get the plan sorted first.', $today->copy()->setTime(16, 34));

        $m1->conversation->participants()->updateExistingPivot($joker->id, ['last_read_at' => $today->copy()->setTime(16, 41)]);

        // Saved messages
        $plans = SavedCollection::firstOrCreate(['user_id' => $joker->id, 'name' => 'Plans & operations']);
        SavedCollection::firstOrCreate(['user_id' => $joker->id, 'name' => 'Places to go']);
        SavedCollection::firstOrCreate(['user_id' => $joker->id, 'name' => 'Links & inspiration']);

        foreach ([$m1, $m2] as $message) {
            SavedMessage::updateOrCreate(
                ['user_id' => $joker->id, 'message_id' => $message->id],
                ['collection_id' => $plans->id],
            );
        }
    }

    /** @param  array<int|string, User>  $members */
    private function group(string $name, string $description, User $admin, array $members): Conversation
    {
        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $name,
            'description' => $description,
            'created_by' => $admin->id,
        ]);

        $attach = [];
        foreach ($members as $member) {
            $attach[$member->id] = [
                'role' => $member->id === $admin->id ? 'admin' : 'member',
                'joined_at' => now(),
            ];
        }
        $conversation->participants()->attach($attach);

        return $conversation;
    }

    private function say(Conversation $conversation, User $sender, string $body, $at): Message
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'type' => 'text',
            'body' => $body,
        ]);

        $message->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

        return $message->setRelation('conversation', $conversation);
    }
}
