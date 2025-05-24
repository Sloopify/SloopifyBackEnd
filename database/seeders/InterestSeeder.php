<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InterestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Interest::create([
            'name' => 'Gaming',
            'image' => 'interests/gaming.png',
            'status' => 'active',
            'category' => 'Entertainment & Hobbies'
        ]);

        \App\Models\Interest::create([
            'name' => 'Reading',
            'image' => 'interests/reading.png', 
            'status' => 'active',
            'category' => 'Education & Learning'
        ]);

        \App\Models\Interest::create([
            'name' => 'Programming',
            'image' => 'interests/programming.png',
            'status' => 'active', 
            'category' => 'Technology & Digital'
        ]);

        \App\Models\Interest::create([
            'name' => 'Yoga',
            'image' => 'interests/yoga.png',
            'status' => 'active',
            'category' => 'Health & Fitness'
        ]);

        \App\Models\Interest::create([
            'name' => 'Entrepreneurship',
            'image' => 'interests/entrepreneurship.png',
            'status' => 'active',
            'category' => 'Career & Business'
        ]);

        \App\Models\Interest::create([
            'name' => 'Travel Photography',
            'image' => 'interests/travel-photography.png',
            'status' => 'active',
            'category' => 'Lifestyle & Travel'
        ]);

        \App\Models\Interest::create([
            'name' => 'Painting',
            'image' => 'interests/painting.png',
            'status' => 'active',
            'category' => 'Art & Creativity'
        ]);

        \App\Models\Interest::create([
            'name' => 'Astronomy',
            'image' => 'interests/astronomy.png',
            'status' => 'active',
            'category' => 'Science & Nature'
        ]);

        \App\Models\Interest::create([
            'name' => 'Cooking',
            'image' => 'interests/cooking.png',
            'status' => 'active',
            'category' => 'Food & Drink'
        ]);

        \App\Models\Interest::create([
            'name' => 'Volunteering',
            'image' => 'interests/volunteering.png',
            'status' => 'active',
            'category' => 'Social & Community'
        ]);
    }
}
