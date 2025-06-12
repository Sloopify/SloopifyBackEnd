<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PersonalOccasionCategory;

class PersonalOccasionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'career_education',
                'description' => 'Career and educational milestones',
                'web_icon' => 'briefcase',
                'mobile_icon' => 'briefcase',
                'status' => 'active'
            ],
            [
                'name' => 'personal_milestones',
                'description' => 'Personal achievements and milestones',
                'web_icon' => 'trophy',
                'mobile_icon' => 'trophy',
                'status' => 'active'
            ],
            [
                'name' => 'relationships',
                'description' => 'Relationship and family occasions',
                'web_icon' => 'heart',
                'mobile_icon' => 'heart',
                'status' => 'active'
            ],
            [
                'name' => 'life_changes',
                'description' => 'Major life transitions and changes',
                'web_icon' => 'map-pin',
                'mobile_icon' => 'map-pin',
                'status' => 'active'
            ],
            [
                'name' => 'celebrations',
                'description' => 'Special celebrations and festivities',
                'web_icon' => 'cake',
                'mobile_icon' => 'cake',
                'status' => 'active'
            ]
        ];

        foreach ($categories as $category) {
            PersonalOccasionCategory::create($category);
        }
    }
}
