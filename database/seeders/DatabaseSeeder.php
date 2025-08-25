<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            SettingSeeder::class,
            UserSeeder::class,
            InterestSeeder::class,
            UserInterestSeeder::class,
            FriendshipSeeder::class,
            FellingSeeder::class,
            ActivitySeeder::class,
            PersonalOccasionCategorySeeder::class,
            PersonalOccasionSeeder::class,
            UserPlaceSeeder::class,
            StoryAudioSeeder::class,
            ReactionSeeder::class,
            
        ]);
    }
}
