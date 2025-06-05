<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Friendship;
use App\Models\User;

class FriendshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users for testing
        $users = User::take(5)->get();
        
        if ($users->count() >= 2) {
            // Create some accepted friendships
            Friendship::create([
                'user_id' => $users[0]->id,
                'friend_id' => $users[1]->id,
                'status' => 'accepted',
                'requested_at' => now()->subDays(5),
                'responded_at' => now()->subDays(4),
            ]);

            if ($users->count() >= 3) {
                Friendship::create([
                    'user_id' => $users[0]->id,
                    'friend_id' => $users[2]->id,
                    'status' => 'accepted',
                    'requested_at' => now()->subDays(3),
                    'responded_at' => now()->subDays(2),
                ]);
            }

            if ($users->count() >= 4) {
                // Create a pending friendship
                Friendship::create([
                    'user_id' => $users[0]->id,
                    'friend_id' => $users[3]->id,
                    'status' => 'pending',
                    'requested_at' => now()->subDay(),
                ]);
            }

            if ($users->count() >= 5) {
                // Create a friendship where user is the recipient
                Friendship::create([
                    'user_id' => $users[4]->id,
                    'friend_id' => $users[0]->id,
                    'status' => 'accepted',
                    'requested_at' => now()->subDays(7),
                    'responded_at' => now()->subDays(6),
                ]);
            }
        }
    }
} 