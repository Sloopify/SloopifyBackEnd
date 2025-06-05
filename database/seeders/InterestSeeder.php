<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InterestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $interests = [
            // Entertainment & Hobbies
            ['name' => 'Movies', 'category' => 'Entertainment & Hobbies', 'image' => 'https://www.flaticon.com/free-icons/movie'],
            ['name' => 'Gaming', 'category' => 'Entertainment & Hobbies', 'image' => 'https://www.flaticon.com/free-icons/game'],
            ['name' => 'Music', 'category' => 'Entertainment & Hobbies', 'image' => 'https://www.flaticon.com/free-icons/music'],
            ['name' => 'Board Games', 'category' => 'Entertainment & Hobbies', 'image' => 'https://www.flaticon.com/free-icons/board-game'],
            ['name' => 'Magic Tricks', 'category' => 'Entertainment & Hobbies', 'image' => 'https://www.flaticon.com/free-icons/magic'],
            ['name' => 'Collecting', 'category' => 'Entertainment & Hobbies', 'image' => 'https://www.flaticon.com/free-icons/collection'],
            ['name' => 'Comedy', 'category' => 'Entertainment & Hobbies', 'image' => 'https://www.flaticon.com/free-icons/comedy'],
            ['name' => 'Theater', 'category' => 'Entertainment & Hobbies', 'image' => 'https://www.flaticon.com/free-icons/theater'],
            ['name' => 'Puzzles', 'category' => 'Entertainment & Hobbies', 'image' => 'https://www.flaticon.com/free-icons/puzzle'],
            ['name' => 'DIY Projects', 'category' => 'Entertainment & Hobbies', 'image' => 'https://www.flaticon.com/free-icons/diy'],

            // Education & Learning
            ['name' => 'Online Courses', 'category' => 'Education & Learning', 'image' => 'https://www.flaticon.com/free-icons/online-learning'],
            ['name' => 'Language Learning', 'category' => 'Education & Learning', 'image' => 'https://www.flaticon.com/free-icons/language'],
            ['name' => 'Reading', 'category' => 'Education & Learning', 'image' => 'https://www.flaticon.com/free-icons/reading'],
            ['name' => 'Workshops', 'category' => 'Education & Learning', 'image' => 'https://www.flaticon.com/free-icons/workshop'],
            ['name' => 'History', 'category' => 'Education & Learning', 'image' => 'https://www.flaticon.com/free-icons/history'],
            ['name' => 'Math', 'category' => 'Education & Learning', 'image' => 'https://www.flaticon.com/free-icons/math'],
            ['name' => 'Science', 'category' => 'Education & Learning', 'image' => 'https://www.flaticon.com/free-icons/science'],
            ['name' => 'Coding', 'category' => 'Education & Learning', 'image' => 'https://www.flaticon.com/free-icons/code'],
            ['name' => 'Study Groups', 'category' => 'Education & Learning', 'image' => 'https://www.flaticon.com/free-icons/group'],
            ['name' => 'Writing', 'category' => 'Education & Learning', 'image' => 'https://www.flaticon.com/free-icons/writing'],

            // You can continue similarly for the rest of the categories...

            // Technology & Digital
            ['name' => 'AI & Machine Learning', 'category' => 'Technology & Digital', 'image' => 'https://www.flaticon.com/free-icons/robot'],
            ['name' => 'Blockchain', 'category' => 'Technology & Digital', 'image' => 'https://www.flaticon.com/free-icons/blockchain'],
            ['name' => 'App Development', 'category' => 'Technology & Digital', 'image' => 'https://www.flaticon.com/free-icons/app'],
            ['name' => 'Web Development', 'category' => 'Technology & Digital', 'image' => 'https://www.flaticon.com/free-icons/web-development'],
            ['name' => 'Cybersecurity', 'category' => 'Technology & Digital', 'image' => 'https://www.flaticon.com/free-icons/cybersecurity'],
            ['name' => 'Gadgets', 'category' => 'Technology & Digital', 'image' => 'https://www.flaticon.com/free-icons/gadget'],
            ['name' => 'Gaming Tech', 'category' => 'Technology & Digital', 'image' => 'https://www.flaticon.com/free-icons/joystick'],
            ['name' => 'Tech News', 'category' => 'Technology & Digital', 'image' => 'https://www.flaticon.com/free-icons/news'],
            ['name' => 'AR/VR', 'category' => 'Technology & Digital', 'image' => 'https://www.flaticon.com/free-icons/vr'],
            ['name' => 'Cloud Computing', 'category' => 'Technology & Digital', 'image' => 'https://www.flaticon.com/free-icons/cloud'],

            // Additional categories omitted for brevity...
        ];

        foreach ($interests as $interest) {
            DB::table('interests')->insert([
                'name' => $interest['name'],
                'category' => $interest['category'],
                'image' => $interest['image'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
