<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PersonalOccasionSetting;
use App\Models\PersonalOccasionCategory;

class PersonalOccasionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category IDs
        $careerEducationCategory = PersonalOccasionCategory::where('name', 'career_education')->first();
        $personalMilestonesCategory = PersonalOccasionCategory::where('name', 'personal_milestones')->first();
        $relationshipsCategory = PersonalOccasionCategory::where('name', 'relationships')->first();
        $lifeChangesCategory = PersonalOccasionCategory::where('name', 'life_changes')->first();
        $celebrationsCategory = PersonalOccasionCategory::where('name', 'celebrations')->first();

        $occasions = [
            [
                'personal_occasion_category_id' => $careerEducationCategory->id,
                'name' => 'new_job',
                'title' => 'New Job',
                'description' => 'Started a new job or career opportunity',
                'web_icon' => 'briefcase',
                'mobile_icon' => 'briefcase',
                'status' => 'active'
            ],
            [
                'personal_occasion_category_id' => $careerEducationCategory->id,
                'name' => 'job_promotion',
                'title' => 'Job Promotion',
                'description' => 'Received a promotion or advancement at work',
                'web_icon' => 'trending-up',
                'mobile_icon' => 'trending-up',
                'status' => 'active'
            ],
            [
                'personal_occasion_category_id' => $careerEducationCategory->id,
                'name' => 'graduation',
                'title' => 'Graduation',
                'description' => 'Graduated from school, college, or university',
                'web_icon' => 'graduation-cap',
                'mobile_icon' => 'graduation-cap',
                'status' => 'active'
            ],
            [
                'personal_occasion_category_id' => $careerEducationCategory->id,
                'name' => 'started_studies',
                'title' => 'Started Studies',
                'description' => 'Began new educational journey or course',
                'web_icon' => 'book-open',
                'mobile_icon' => 'book-open',
                'status' => 'active'
            ],
            [
                'personal_occasion_category_id' => $relationshipsCategory->id,
                'name' => 'relationship_status',
                'title' => 'Relationship Status',
                'description' => 'Changed relationship status or milestone',
                'web_icon' => 'heart',
                'mobile_icon' => 'heart',
                'status' => 'active'
            ],
            [
                'personal_occasion_category_id' => $lifeChangesCategory->id,
                'name' => 'moved_city',
                'title' => 'Moved City',
                'description' => 'Relocated to a new city or place',
                'web_icon' => 'map-pin',
                'mobile_icon' => 'map-pin',
                'status' => 'active'
            ],
            [
                'personal_occasion_category_id' => $celebrationsCategory->id,
                'name' => 'birthday',
                'title' => 'Birthday',
                'description' => 'Celebrating another year of life',
                'web_icon' => 'cake',
                'mobile_icon' => 'cake',
                'status' => 'active'
            ],
            [
                'personal_occasion_category_id' => $celebrationsCategory->id,
                'name' => 'anniversary',
                'title' => 'Anniversary',
                'description' => 'Celebrating a special anniversary',
                'web_icon' => 'calendar-heart',
                'mobile_icon' => 'calendar-heart',
                'status' => 'active'
            ],
            [
                'personal_occasion_category_id' => $personalMilestonesCategory->id,
                'name' => 'achievement',
                'title' => 'Achievement',
                'description' => 'Accomplished a personal goal or milestone',
                'web_icon' => 'trophy',
                'mobile_icon' => 'trophy',
                'status' => 'active'
            ],
            [
                'personal_occasion_category_id' => $personalMilestonesCategory->id,
                'name' => 'travel',
                'title' => 'Travel',
                'description' => 'Went on a trip or travel adventure',
                'web_icon' => 'plane',
                'mobile_icon' => 'plane',
                'status' => 'active'
            ],
            [
                'personal_occasion_category_id' => $personalMilestonesCategory->id,
                'name' => 'other',
                'title' => 'Other',
                'description' => 'Other personal occasions not listed above',
                'web_icon' => 'star',
                'mobile_icon' => 'star',
                'status' => 'active'
            ]
        ];

        foreach ($occasions as $occasion) {
            PersonalOccasionSetting::create($occasion);
        }
    }
}
