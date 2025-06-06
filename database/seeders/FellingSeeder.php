<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PostFeeling;
use App\Models\PostActivity;
use Illuminate\Support\Facades\DB;

class FellingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PostFeeling::truncate();
        PostActivity::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Seed Feelings (35+ feelings)
        $feelings = [
            ['name' => 'Happy', 'mobile_icon' => 'happy.png', 'web_icon' => 'happy.svg', 'status' => 'active'],
            ['name' => 'Sad', 'mobile_icon' => 'sad.png', 'web_icon' => 'sad.svg', 'status' => 'active'],
            ['name' => 'Excited', 'mobile_icon' => 'excited.png', 'web_icon' => 'excited.svg', 'status' => 'active'],
            ['name' => 'Grateful', 'mobile_icon' => 'grateful.png', 'web_icon' => 'grateful.svg', 'status' => 'active'],
            ['name' => 'Blessed', 'mobile_icon' => 'blessed.png', 'web_icon' => 'blessed.svg', 'status' => 'active'],
            ['name' => 'Loved', 'mobile_icon' => 'loved.png', 'web_icon' => 'loved.svg', 'status' => 'active'],
            ['name' => 'Proud', 'mobile_icon' => 'proud.png', 'web_icon' => 'proud.svg', 'status' => 'active'],
            ['name' => 'Motivated', 'mobile_icon' => 'motivated.png', 'web_icon' => 'motivated.svg', 'status' => 'active'],
            ['name' => 'Relaxed', 'mobile_icon' => 'relaxed.png', 'web_icon' => 'relaxed.svg', 'status' => 'active'],
            ['name' => 'Peaceful', 'mobile_icon' => 'peaceful.png', 'web_icon' => 'peaceful.svg', 'status' => 'active'],
            ['name' => 'Frustrated', 'mobile_icon' => 'frustrated.png', 'web_icon' => 'frustrated.svg', 'status' => 'active'],
            ['name' => 'Angry', 'mobile_icon' => 'angry.png', 'web_icon' => 'angry.svg', 'status' => 'active'],
            ['name' => 'Confused', 'mobile_icon' => 'confused.png', 'web_icon' => 'confused.svg', 'status' => 'active'],
            ['name' => 'Hopeful', 'mobile_icon' => 'hopeful.png', 'web_icon' => 'hopeful.svg', 'status' => 'active'],
            ['name' => 'Nostalgic', 'mobile_icon' => 'nostalgic.png', 'web_icon' => 'nostalgic.svg', 'status' => 'active'],
            ['name' => 'Amazed', 'mobile_icon' => 'amazed.png', 'web_icon' => 'amazed.svg', 'status' => 'active'],
            ['name' => 'Surprised', 'mobile_icon' => 'surprised.png', 'web_icon' => 'surprised.svg', 'status' => 'active'],
            ['name' => 'Thoughtful', 'mobile_icon' => 'thoughtful.png', 'web_icon' => 'thoughtful.svg', 'status' => 'active'],
            ['name' => 'Determined', 'mobile_icon' => 'determined.png', 'web_icon' => 'determined.svg', 'status' => 'active'],
            ['name' => 'Inspired', 'mobile_icon' => 'inspired.png', 'web_icon' => 'inspired.svg', 'status' => 'active'],
            ['name' => 'Content', 'mobile_icon' => 'content.png', 'web_icon' => 'content.svg', 'status' => 'active'],
            ['name' => 'Accomplished', 'mobile_icon' => 'accomplished.png', 'web_icon' => 'accomplished.svg', 'status' => 'active'],
            ['name' => 'Energetic', 'mobile_icon' => 'energetic.png', 'web_icon' => 'energetic.svg', 'status' => 'active'],
            ['name' => 'Tired', 'mobile_icon' => 'tired.png', 'web_icon' => 'tired.svg', 'status' => 'active'],
            ['name' => 'Stressed', 'mobile_icon' => 'stressed.png', 'web_icon' => 'stressed.svg', 'status' => 'active'],
            ['name' => 'Overwhelmed', 'mobile_icon' => 'overwhelmed.png', 'web_icon' => 'overwhelmed.svg', 'status' => 'active'],
            ['name' => 'Anxious', 'mobile_icon' => 'anxious.png', 'web_icon' => 'anxious.svg', 'status' => 'active'],
            ['name' => 'Curious', 'mobile_icon' => 'curious.png', 'web_icon' => 'curious.svg', 'status' => 'active'],
            ['name' => 'Adventurous', 'mobile_icon' => 'adventurous.png', 'web_icon' => 'adventurous.svg', 'status' => 'active'],
            ['name' => 'Lonely', 'mobile_icon' => 'lonely.png', 'web_icon' => 'lonely.svg', 'status' => 'active'],
            ['name' => 'Cheerful', 'mobile_icon' => 'cheerful.png', 'web_icon' => 'cheerful.svg', 'status' => 'active'],
            ['name' => 'Optimistic', 'mobile_icon' => 'optimistic.png', 'web_icon' => 'optimistic.svg', 'status' => 'active'],
            ['name' => 'Disappointed', 'mobile_icon' => 'disappointed.png', 'web_icon' => 'disappointed.svg', 'status' => 'active'],
            ['name' => 'Embarrassed', 'mobile_icon' => 'embarrassed.png', 'web_icon' => 'embarrassed.svg', 'status' => 'active'],
            ['name' => 'Lucky', 'mobile_icon' => 'lucky.png', 'web_icon' => 'lucky.svg', 'status' => 'active'],
            ['name' => 'Silly', 'mobile_icon' => 'silly.png', 'web_icon' => 'silly.svg', 'status' => 'active'],
            ['name' => 'Playful', 'mobile_icon' => 'playful.png', 'web_icon' => 'playful.svg', 'status' => 'active'],
            ['name' => 'Fantastic', 'mobile_icon' => 'fantastic.png', 'web_icon' => 'fantastic.svg', 'status' => 'active'],
        ];

        foreach ($feelings as $feeling) {
            PostFeeling::create($feeling);
        }

        $this->command->info('Successfully seeded ' . count($feelings) . ' feelings!');
    }

}
