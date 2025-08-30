<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Skill;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            // 🧑‍💻 Technology & Digital
            ['category' => 'Technology & Digital', 'name' => 'Programming', 'sort_order' => 1],
            ['category' => 'Technology & Digital', 'name' => 'Web Development', 'sort_order' => 2],
            ['category' => 'Technology & Digital', 'name' => 'Mobile Development', 'sort_order' => 3],
            ['category' => 'Technology & Digital', 'name' => 'Data Science', 'sort_order' => 4],
            ['category' => 'Technology & Digital', 'name' => 'Artificial Intelligence', 'sort_order' => 5],
            ['category' => 'Technology & Digital', 'name' => 'Cybersecurity', 'sort_order' => 6],
            ['category' => 'Technology & Digital', 'name' => 'Cloud Computing', 'sort_order' => 7],
            ['category' => 'Technology & Digital', 'name' => 'Blockchain', 'sort_order' => 8],
            ['category' => 'Technology & Digital', 'name' => 'UI/UX Design', 'sort_order' => 9],
            ['category' => 'Technology & Digital', 'name' => 'Graphic Design', 'sort_order' => 10],
            ['category' => 'Technology & Digital', 'name' => 'Video Editing', 'sort_order' => 11],

            // 🎨 Creative & Arts
            ['category' => 'Creative & Arts', 'name' => 'Drawing', 'sort_order' => 1],
            ['category' => 'Creative & Arts', 'name' => 'Painting', 'sort_order' => 2],
            ['category' => 'Creative & Arts', 'name' => 'Photography', 'sort_order' => 3],
            ['category' => 'Creative & Arts', 'name' => 'Filmmaking', 'sort_order' => 4],
            ['category' => 'Creative & Arts', 'name' => 'Music Production', 'sort_order' => 5],
            ['category' => 'Creative & Arts', 'name' => 'Singing', 'sort_order' => 6],
            ['category' => 'Creative & Arts', 'name' => 'Acting', 'sort_order' => 7],
            ['category' => 'Creative & Arts', 'name' => 'Dancing', 'sort_order' => 8],
            ['category' => 'Creative & Arts', 'name' => 'Creative Writing', 'sort_order' => 9],
            ['category' => 'Creative & Arts', 'name' => 'Fashion Design', 'sort_order' => 10],

            // 📈 Business & Finance
            ['category' => 'Business & Finance', 'name' => 'Marketing', 'sort_order' => 1],
            ['category' => 'Business & Finance', 'name' => 'Sales', 'sort_order' => 2],
            ['category' => 'Business & Finance', 'name' => 'Entrepreneurship', 'sort_order' => 3],
            ['category' => 'Business & Finance', 'name' => 'Project Management', 'sort_order' => 4],
            ['category' => 'Business & Finance', 'name' => 'Business Strategy', 'sort_order' => 5],
            ['category' => 'Business & Finance', 'name' => 'Public Speaking', 'sort_order' => 6],
            ['category' => 'Business & Finance', 'name' => 'Leadership', 'sort_order' => 7],
            ['category' => 'Business & Finance', 'name' => 'Negotiation', 'sort_order' => 8],
            ['category' => 'Business & Finance', 'name' => 'Finance & Accounting', 'sort_order' => 9],
            ['category' => 'Business & Finance', 'name' => 'Investing & Trading', 'sort_order' => 10],

            // 🌍 Lifestyle & Personal Growth
            ['category' => 'Lifestyle & Personal Growth', 'name' => 'Cooking', 'sort_order' => 1],
            ['category' => 'Lifestyle & Personal Growth', 'name' => 'Fitness', 'sort_order' => 2],
            ['category' => 'Lifestyle & Personal Growth', 'name' => 'Yoga', 'sort_order' => 3],
            ['category' => 'Lifestyle & Personal Growth', 'name' => 'Meditation', 'sort_order' => 4],
            ['category' => 'Lifestyle & Personal Growth', 'name' => 'Travel Planning', 'sort_order' => 5],
            ['category' => 'Lifestyle & Personal Growth', 'name' => 'Blogging', 'sort_order' => 6],
            ['category' => 'Lifestyle & Personal Growth', 'name' => 'Language Learning', 'sort_order' => 7],
            ['category' => 'Lifestyle & Personal Growth', 'name' => 'DIY & Crafts', 'sort_order' => 8],
            ['category' => 'Lifestyle & Personal Growth', 'name' => 'Gardening', 'sort_order' => 9],
            ['category' => 'Lifestyle & Personal Growth', 'name' => 'Time Management', 'sort_order' => 10],

            // 🔬 Science & Education
            ['category' => 'Science & Education', 'name' => 'Mathematics', 'sort_order' => 1],
            ['category' => 'Science & Education', 'name' => 'Physics', 'sort_order' => 2],
            ['category' => 'Science & Education', 'name' => 'Chemistry', 'sort_order' => 3],
            ['category' => 'Science & Education', 'name' => 'Biology', 'sort_order' => 4],
            ['category' => 'Science & Education', 'name' => 'Research', 'sort_order' => 5],
            ['category' => 'Science & Education', 'name' => 'Teaching', 'sort_order' => 6],
            ['category' => 'Science & Education', 'name' => 'Astronomy', 'sort_order' => 7],
            ['category' => 'Science & Education', 'name' => 'Psychology', 'sort_order' => 8],
            ['category' => 'Science & Education', 'name' => 'History', 'sort_order' => 9],
            ['category' => 'Science & Education', 'name' => 'Philosophy', 'sort_order' => 10],

            // 🤝 Social & Community
            ['category' => 'Social & Community', 'name' => 'Event Planning', 'sort_order' => 1],
            ['category' => 'Social & Community', 'name' => 'Volunteering', 'sort_order' => 2],
            ['category' => 'Social & Community', 'name' => 'Mentoring', 'sort_order' => 3],
            ['category' => 'Social & Community', 'name' => 'Networking', 'sort_order' => 4],
            ['category' => 'Social & Community', 'name' => 'Conflict Resolution', 'sort_order' => 5],
            ['category' => 'Social & Community', 'name' => 'Counseling', 'sort_order' => 6],
            ['category' => 'Social & Community', 'name' => 'Team Building', 'sort_order' => 7],
            ['category' => 'Social & Community', 'name' => 'Cultural Awareness', 'sort_order' => 8],
            ['category' => 'Social & Community', 'name' => 'Communication', 'sort_order' => 9],
            ['category' => 'Social & Community', 'name' => 'Emotional Intelligence', 'sort_order' => 10],

            // 🎮 Gaming & Entertainment
            ['category' => 'Gaming & Entertainment', 'name' => 'Esports', 'sort_order' => 1],
            ['category' => 'Gaming & Entertainment', 'name' => 'Game Design', 'sort_order' => 2],
            ['category' => 'Gaming & Entertainment', 'name' => 'Game Streaming', 'sort_order' => 3],
            ['category' => 'Gaming & Entertainment', 'name' => 'Board Games', 'sort_order' => 4],
            ['category' => 'Gaming & Entertainment', 'name' => 'Roleplaying', 'sort_order' => 5],
            ['category' => 'Gaming & Entertainment', 'name' => 'Storytelling', 'sort_order' => 6],
            ['category' => 'Gaming & Entertainment', 'name' => 'Animation', 'sort_order' => 7],
            ['category' => 'Gaming & Entertainment', 'name' => 'Comic Creation', 'sort_order' => 8],
            ['category' => 'Gaming & Entertainment', 'name' => 'Virtual Reality', 'sort_order' => 9],
            ['category' => 'Gaming & Entertainment', 'name' => 'Memes & Internet Culture', 'sort_order' => 10],
        ];

        foreach ($skills as $skill) {
            Skill::updateOrCreate(
                ['name' => $skill['name']], // Check if skill exists by name
                [
                    'category' => $skill['category'],
                    'status' => 'active',
                    'sort_order' => $skill['sort_order']
                ]
            );
        }

        $this->command->info('Skills seeded successfully!');
    }
}
