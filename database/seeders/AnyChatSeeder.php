<?php

namespace SaamMi\AnyChat\Database\Seeders;

use Illuminate\Database\Seeder;
// Ensure you import your models using their full package namespace
use SaamMi\AnyChat\Models\Guest;
use SaamMi\AnyChat\Models\Conversation;
use SaamMi\AnyChat\Models\Message;
use SaamMi\AnyChat\Models\Participant;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

use App\Models\User;

class AnyChatSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure an Admin Support User Exists
        $admin = User::firstOrCreate(
            ['email' => 'admin@support.com'],
            [
                'name' => 'Support Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Seeding 10 Guests with messages...');
        $this->seedGuests($admin);

        $this->command->info('Seeding 10 Users with messages...');
        $this->seedUsers($admin);

        $this->command->info('Chat testing data seeded successfully!');


$users = User::all();

foreach ( $users as $index => $usr) {
    // Note: We are adding $admin to the `use` statement here so we can add them to the group
    $usr->ownedTeams->each(function($team) use ($users, $usr, $admin) {
        // Generate a random group name
        $groupName = Str::random(12);

        // 1. Create the Conversation FIRST so we have the ID
        $conversation = Conversation::create(['type' => 'group']);

        // 2. Insert the Group AND link the conversation_id
        DB::table('groups')->insert([
           'name' => $groupName,
           'conversation_id' => $conversation->id // Required by your Livewire component
        ]);

        // 3. Ensure the Admin is always a participant so the UI doesn't crash
        $conversation->participants()->create([
            'participantable_id' => $admin->id, 
            'participantable_type' => \App\Models\User::class, 
            'role' => 'owner'
        ]);
    
        // 4. Pick 6 random users
       $randomUsers = $users->reject(fn($u) => $u->id === $admin->id)->random(6);

        foreach ( $randomUsers as $r) {

 $team->memberships()->firstOrCreate(
                ['user_id' => $r->id],
                ['role' => 'member', 'team_id' => $team->id]
            );



            $team->groupMemberships()->firstOrCreate(
                ['user_id' => $r->id],
                ['role' => 'member', 'group_name' => $groupName, 'team_id' => $team->id]
            );
            
            // 5. Capture the created participant in a variable
            $participant = $conversation->participants()->firstOrCreate([
                'participantable_id' => $r->id, 
                'participantable_type' => \App\Models\User::class, 
                'role' => 'member' // Setting random users as members
            ]);

            // 6. Use $participant->id instead of $r->id for the message!
            $conversation->messages()->create([
                'body' => fake()->realText(rand(30, 80)),
                'participant_id' => $participant->id,
                'type' => 'text',
            ]);
        }
    });
}



    }
    protected function seedGuests(User $admin): void
    {
        for ($i = 1; $i <= 10; $i++) {
            // Guests use a 16-byte hex string ID based on your handshake logic
            $guestId = bin2hex(random_bytes(16));
            
            $guest = Guest::create([
                'id' => $guestId,
            ]);

            $this->createConversationAndMessages($admin, $guest, 'SaamMi\AnyChat\Models\Guest');
        }
    }

    protected function seedUsers(User $admin): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $user = User::factory()->create([
                'name' => "Test User {$i}",
                'email' => "user{$i}@example.com",
            ]);

            $this->createConversationAndMessages($admin, $user, 'App\Models\User');
        }
    }
    protected function createConversationAndMessages(User $admin, $client, string $clientType): void
    {
        // 1. Create the Conversation
        $conversation = \SaamMi\AnyChat\Models\Conversation::create(['type' => 'direct']);

        // 2. Add Admin Participant
        $adminParticipant = Participant::create([
            'conversation_id' => $conversation->id,
            'participantable_id' => $admin->id,
            'participantable_type' => 'App\Models\User',
            'role' => 'owner'
        ]);

        // 3. Add Client (User/Guest) Participant
        $clientParticipant = Participant::create([
            'conversation_id' => $conversation->id,
            'participantable_id' => $client->id,
            'participantable_type' => $clientType,
            'role' => 'participant'
        ]);

        // 4. Generate Back-and-Forth Faker Messages
        $messages = [
            [
                'participant' => $clientParticipant,
                // Client asks a question (between 30 and 80 characters)
                'body' => fake()->realText(rand(30, 80)), 
            ],
            [
                'participant' => $adminParticipant,
                // Admin replies with a slightly longer explanation
                'body' => fake()->realText(rand(50, 120)), 
            ],
            [
                'participant' => $clientParticipant,
                // Client sends a short follow-up
                'body' => fake()->sentence(rand(3, 8)), 
            ],
            [
                'participant' => $adminParticipant,
                // Admin concludes
                'body' => fake()->realText(rand(40, 90)), 
            ],
        ];

        // Space out the timestamps so they sort correctly in your UI
        $baseTime = now()->subHours(rand(1, 48));

        foreach ($messages as $index => $msg) {
            $conversation->messages()->create([
                'body' => $msg['body'],
                'participant_id' => $msg['participant']->id,
                'type' => 'text',
                'created_at' => (clone $baseTime)->addMinutes($index * 5),
                'updated_at' => (clone $baseTime)->addMinutes($index * 5),
            ]);
        }



       
    }

   
        
       
}