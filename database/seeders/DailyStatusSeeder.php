<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DailyStatus;

class DailyStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dailyStatuses = [
            [
                'name' => 'Happy',
                'web_icon' => '😊',
                'mobile_icon' => 'happy_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Sad',
                'web_icon' => '😢',
                'mobile_icon' => 'sad_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Angry',
                'web_icon' => '😠',
                'mobile_icon' => 'angry_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Excited',
                'web_icon' => '🤩',
                'mobile_icon' => 'excited_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Tired',
                'web_icon' => '😴',
                'mobile_icon' => 'tired_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Energetic',
                'web_icon' => '⚡',
                'mobile_icon' => 'energetic_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Calm',
                'web_icon' => '😌',
                'mobile_icon' => 'calm_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Stressed',
                'web_icon' => '😰',
                'mobile_icon' => 'stressed_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Confident',
                'web_icon' => '😎',
                'mobile_icon' => 'confident_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Lonely',
                'web_icon' => '🥺',
                'mobile_icon' => 'lonely_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Grateful',
                'web_icon' => '🙏',
                'mobile_icon' => 'grateful_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Focused',
                'web_icon' => '🎯',
                'mobile_icon' => 'focused_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Creative',
                'web_icon' => '🎨',
                'mobile_icon' => 'creative_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Motivated',
                'web_icon' => '💪',
                'mobile_icon' => 'motivated_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Relaxed',
                'web_icon' => '😌',
                'mobile_icon' => 'relaxed_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Anxious',
                'web_icon' => '😟',
                'mobile_icon' => 'anxious_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Optimistic',
                'web_icon' => '😄',
                'mobile_icon' => 'optimistic_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Pessimistic',
                'web_icon' => '😔',
                'mobile_icon' => 'pessimistic_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Inspired',
                'web_icon' => '✨',
                'mobile_icon' => 'inspired_icon.png',
                'status' => true,
            ],
            [
                'name' => 'Bored',
                'web_icon' => '😐',
                'mobile_icon' => 'bored_icon.png',
                'status' => true,
            ],
        ];

        foreach ($dailyStatuses as $status) {
            DailyStatus::create($status);
        }

        $this->command->info('Daily statuses seeded successfully!');
    }
}
