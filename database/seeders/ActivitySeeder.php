<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PostActivity;
use Illuminate\Support\Facades\DB;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PostActivity::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

         // Seed Activities (40+ activities organized by categories)
         $activities = [
            // Entertainment & Media
            ['name' => 'Watching a movie', 'category' => 'entertainment', 'mobile_icon' => 'movie.png', 'web_icon' => 'movie.svg', 'status' => 'active'],
            ['name' => 'Listening to music', 'category' => 'entertainment', 'mobile_icon' => 'music.png', 'web_icon' => 'music.svg', 'status' => 'active'],
            ['name' => 'Reading a book', 'category' => 'entertainment', 'mobile_icon' => 'book.png', 'web_icon' => 'book.svg', 'status' => 'active'],
            ['name' => 'Playing video games', 'category' => 'entertainment', 'mobile_icon' => 'gaming.png', 'web_icon' => 'gaming.svg', 'status' => 'active'],
            ['name' => 'Watching TV series', 'category' => 'entertainment', 'mobile_icon' => 'tv.png', 'web_icon' => 'tv.svg', 'status' => 'active'],
            ['name' => 'Attending a concert', 'category' => 'entertainment', 'mobile_icon' => 'concert.png', 'web_icon' => 'concert.svg', 'status' => 'active'],

            // Food & Dining
            ['name' => 'Eating dinner', 'category' => 'food', 'mobile_icon' => 'dinner.png', 'web_icon' => 'dinner.svg', 'status' => 'active'],
            ['name' => 'Having breakfast', 'category' => 'food', 'mobile_icon' => 'breakfast.png', 'web_icon' => 'breakfast.svg', 'status' => 'active'],
            ['name' => 'Cooking', 'category' => 'food', 'mobile_icon' => 'cooking.png', 'web_icon' => 'cooking.svg', 'status' => 'active'],
            ['name' => 'Having coffee', 'category' => 'food', 'mobile_icon' => 'coffee.png', 'web_icon' => 'coffee.svg', 'status' => 'active'],
            ['name' => 'Trying new food', 'category' => 'food', 'mobile_icon' => 'new_food.png', 'web_icon' => 'new_food.svg', 'status' => 'active'],
            ['name' => 'Having lunch', 'category' => 'food', 'mobile_icon' => 'lunch.png', 'web_icon' => 'lunch.svg', 'status' => 'active'],

            // Travel & Adventure
            ['name' => 'Traveling', 'category' => 'travel', 'mobile_icon' => 'travel.png', 'web_icon' => 'travel.svg', 'status' => 'active'],
            ['name' => 'Exploring new places', 'category' => 'travel', 'mobile_icon' => 'explore.png', 'web_icon' => 'explore.svg', 'status' => 'active'],
            ['name' => 'Going on vacation', 'category' => 'travel', 'mobile_icon' => 'vacation.png', 'web_icon' => 'vacation.svg', 'status' => 'active'],
            ['name' => 'Road trip', 'category' => 'travel', 'mobile_icon' => 'road_trip.png', 'web_icon' => 'road_trip.svg', 'status' => 'active'],
            ['name' => 'Camping', 'category' => 'travel', 'mobile_icon' => 'camping.png', 'web_icon' => 'camping.svg', 'status' => 'active'],
            ['name' => 'Hiking', 'category' => 'travel', 'mobile_icon' => 'hiking.png', 'web_icon' => 'hiking.svg', 'status' => 'active'],

            // Sports & Fitness
            ['name' => 'Working out', 'category' => 'fitness', 'mobile_icon' => 'workout.png', 'web_icon' => 'workout.svg', 'status' => 'active'],
            ['name' => 'Playing sports', 'category' => 'fitness', 'mobile_icon' => 'sports.png', 'web_icon' => 'sports.svg', 'status' => 'active'],
            ['name' => 'Running', 'category' => 'fitness', 'mobile_icon' => 'running.png', 'web_icon' => 'running.svg', 'status' => 'active'],
            ['name' => 'Swimming', 'category' => 'fitness', 'mobile_icon' => 'swimming.png', 'web_icon' => 'swimming.svg', 'status' => 'active'],
            ['name' => 'Cycling', 'category' => 'fitness', 'mobile_icon' => 'cycling.png', 'web_icon' => 'cycling.svg', 'status' => 'active'],
            ['name' => 'Playing football', 'category' => 'fitness', 'mobile_icon' => 'football.png', 'web_icon' => 'football.svg', 'status' => 'active'],

            // Social & Relationships
            ['name' => 'Hanging out with friends', 'category' => 'social', 'mobile_icon' => 'friends.png', 'web_icon' => 'friends.svg', 'status' => 'active'],
            ['name' => 'Spending time with family', 'category' => 'social', 'mobile_icon' => 'family.png', 'web_icon' => 'family.svg', 'status' => 'active'],
            ['name' => 'Going on a date', 'category' => 'social', 'mobile_icon' => 'date.png', 'web_icon' => 'date.svg', 'status' => 'active'],
            ['name' => 'Attending a party', 'category' => 'social', 'mobile_icon' => 'party.png', 'web_icon' => 'party.svg', 'status' => 'active'],
            ['name' => 'Meeting new people', 'category' => 'social', 'mobile_icon' => 'meeting.png', 'web_icon' => 'meeting.svg', 'status' => 'active'],
            ['name' => 'Having a reunion', 'category' => 'social', 'mobile_icon' => 'reunion.png', 'web_icon' => 'reunion.svg', 'status' => 'active'],

            // Work & Study
            ['name' => 'Working', 'category' => 'work', 'mobile_icon' => 'work.png', 'web_icon' => 'work.svg', 'status' => 'active'],
            ['name' => 'Studying', 'category' => 'work', 'mobile_icon' => 'study.png', 'web_icon' => 'study.svg', 'status' => 'active'],
            ['name' => 'Attending a meeting', 'category' => 'work', 'mobile_icon' => 'meeting_work.png', 'web_icon' => 'meeting_work.svg', 'status' => 'active'],
            ['name' => 'Learning something new', 'category' => 'work', 'mobile_icon' => 'learning.png', 'web_icon' => 'learning.svg', 'status' => 'active'],
            ['name' => 'Working from home', 'category' => 'work', 'mobile_icon' => 'work_home.png', 'web_icon' => 'work_home.svg', 'status' => 'active'],

            // Health & Wellness
            ['name' => 'Practicing yoga', 'category' => 'wellness', 'mobile_icon' => 'yoga.png', 'web_icon' => 'yoga.svg', 'status' => 'active'],
            ['name' => 'Meditating', 'category' => 'wellness', 'mobile_icon' => 'meditation.png', 'web_icon' => 'meditation.svg', 'status' => 'active'],
            ['name' => 'Going to spa', 'category' => 'wellness', 'mobile_icon' => 'spa.png', 'web_icon' => 'spa.svg', 'status' => 'active'],
            ['name' => 'Getting a massage', 'category' => 'wellness', 'mobile_icon' => 'massage.png', 'web_icon' => 'massage.svg', 'status' => 'active'],
            ['name' => 'Taking a walk', 'category' => 'wellness', 'mobile_icon' => 'walk.png', 'web_icon' => 'walk.svg', 'status' => 'active'],

            // Hobbies & Creativity
            ['name' => 'Drawing', 'category' => 'creative', 'mobile_icon' => 'drawing.png', 'web_icon' => 'drawing.svg', 'status' => 'active'],
            ['name' => 'Painting', 'category' => 'creative', 'mobile_icon' => 'painting.png', 'web_icon' => 'painting.svg', 'status' => 'active'],
            ['name' => 'Taking photos', 'category' => 'creative', 'mobile_icon' => 'photography.png', 'web_icon' => 'photography.svg', 'status' => 'active'],
            ['name' => 'Writing', 'category' => 'creative', 'mobile_icon' => 'writing.png', 'web_icon' => 'writing.svg', 'status' => 'active'],
            ['name' => 'Playing music', 'category' => 'creative', 'mobile_icon' => 'play_music.png', 'web_icon' => 'play_music.svg', 'status' => 'active'],
            ['name' => 'Crafting', 'category' => 'creative', 'mobile_icon' => 'crafting.png', 'web_icon' => 'crafting.svg', 'status' => 'active'],

            // Events & Celebrations
            ['name' => 'Celebrating', 'category' => 'celebration', 'mobile_icon' => 'celebrate.png', 'web_icon' => 'celebrate.svg', 'status' => 'active'],
            ['name' => 'Attending a wedding', 'category' => 'celebration', 'mobile_icon' => 'wedding.png', 'web_icon' => 'wedding.svg', 'status' => 'active'],
            ['name' => 'Having a birthday party', 'category' => 'celebration', 'mobile_icon' => 'birthday.png', 'web_icon' => 'birthday.svg', 'status' => 'active'],
            ['name' => 'Graduating', 'category' => 'celebration', 'mobile_icon' => 'graduation.png', 'web_icon' => 'graduation.svg', 'status' => 'active'],
        ];

        foreach ($activities as $activity) {
            PostActivity::create($activity);
        }

        $this->command->info('Successfully seeded ' . count($activities) . ' activities!');
    }

}
