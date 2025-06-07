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
            ['name' => 'Movies', 'category' => 'Entertainment & Hobbies', 'web_icon' => 'https://www.flaticon.com/free-icons/movie', 'mobile_icon' => 'https://www.flaticon.com/free-icons/movie'],
            ['name' => 'Gaming', 'category' => 'Entertainment & Hobbies', 'web_icon' => 'https://www.flaticon.com/free-icons/game', 'mobile_icon' => 'https://www.flaticon.com/free-icons/game'],
            ['name' => 'Music', 'category' => 'Entertainment & Hobbies', 'web_icon' => 'https://www.flaticon.com/free-icons/music', 'mobile_icon' => 'https://www.flaticon.com/free-icons/music'],
            ['name' => 'Board Games', 'category' => 'Entertainment & Hobbies', 'web_icon' => 'https://www.flaticon.com/free-icons/board-game', 'mobile_icon' => 'https://www.flaticon.com/free-icons/board-game'],
            ['name' => 'Magic Tricks', 'category' => 'Entertainment & Hobbies', 'web_icon' => 'https://www.flaticon.com/free-icons/magic', 'mobile_icon' => 'https://www.flaticon.com/free-icons/magic'],
            ['name' => 'Collecting', 'category' => 'Entertainment & Hobbies', 'web_icon' => 'https://www.flaticon.com/free-icons/collection', 'mobile_icon' => 'https://www.flaticon.com/free-icons/collection'],
            ['name' => 'Comedy', 'category' => 'Entertainment & Hobbies', 'web_icon' => 'https://www.flaticon.com/free-icons/comedy', 'mobile_icon' => 'https://www.flaticon.com/free-icons/comedy'],
            ['name' => 'Theater', 'category' => 'Entertainment & Hobbies', 'web_icon' => 'https://www.flaticon.com/free-icons/theater', 'mobile_icon' => 'https://www.flaticon.com/free-icons/theater'],
            ['name' => 'Puzzles', 'category' => 'Entertainment & Hobbies', 'web_icon' => 'https://www.flaticon.com/free-icons/puzzle', 'mobile_icon' => 'https://www.flaticon.com/free-icons/puzzle'],
            ['name' => 'DIY Projects', 'category' => 'Entertainment & Hobbies', 'web_icon' => 'https://www.flaticon.com/free-icons/diy', 'mobile_icon' => 'https://www.flaticon.com/free-icons/diy'],

            // Education & Learning
            ['name' => 'Online Courses', 'category' => 'Education & Learning', 'web_icon' => 'https://www.flaticon.com/free-icons/online-learning', 'mobile_icon' => 'https://www.flaticon.com/free-icons/online-learning'],
            ['name' => 'Language Learning', 'category' => 'Education & Learning', 'web_icon' => 'https://www.flaticon.com/free-icons/language', 'mobile_icon' => 'https://www.flaticon.com/free-icons/language'],
            ['name' => 'Reading', 'category' => 'Education & Learning', 'web_icon' => 'https://www.flaticon.com/free-icons/reading', 'mobile_icon' => 'https://www.flaticon.com/free-icons/reading'],
            ['name' => 'Workshops', 'category' => 'Education & Learning', 'web_icon' => 'https://www.flaticon.com/free-icons/workshop', 'mobile_icon' => 'https://www.flaticon.com/free-icons/workshop'],
            ['name' => 'History', 'category' => 'Education & Learning', 'web_icon' => 'https://www.flaticon.com/free-icons/history', 'mobile_icon' => 'https://www.flaticon.com/free-icons/history'],
            ['name' => 'Math', 'category' => 'Education & Learning', 'web_icon' => 'https://www.flaticon.com/free-icons/math', 'mobile_icon' => 'https://www.flaticon.com/free-icons/math'],
            ['name' => 'Science', 'category' => 'Education & Learning', 'web_icon' => 'https://www.flaticon.com/free-icons/science', 'mobile_icon' => 'https://www.flaticon.com/free-icons/science'],
            ['name' => 'Coding', 'category' => 'Education & Learning', 'web_icon' => 'https://www.flaticon.com/free-icons/code', 'mobile_icon' => 'https://www.flaticon.com/free-icons/code'],
            ['name' => 'Study Groups', 'category' => 'Education & Learning', 'web_icon' => 'https://www.flaticon.com/free-icons/group', 'mobile_icon' => 'https://www.flaticon.com/free-icons/group'],
            ['name' => 'Writing', 'category' => 'Education & Learning', 'web_icon' => 'https://www.flaticon.com/free-icons/writing', 'mobile_icon' => 'https://www.flaticon.com/free-icons/writing'],

            // You can continue similarly for the rest of the categories...

            // Technology & Digital
            ['name' => 'AI & Machine Learning', 'category' => 'Technology & Digital', 'web_icon' => 'https://www.flaticon.com/free-icons/robot', 'mobile_icon' => 'https://www.flaticon.com/free-icons/robot'],
            ['name' => 'Blockchain', 'category' => 'Technology & Digital', 'web_icon' => 'https://www.flaticon.com/free-icons/blockchain', 'mobile_icon' => 'https://www.flaticon.com/free-icons/blockchain'],
            ['name' => 'App Development', 'category' => 'Technology & Digital', 'web_icon' => 'https://www.flaticon.com/free-icons/app', 'mobile_icon' => 'https://www.flaticon.com/free-icons/app'],
            ['name' => 'Web Development', 'category' => 'Technology & Digital', 'web_icon' => 'https://www.flaticon.com/free-icons/web-development', 'mobile_icon' => 'https://www.flaticon.com/free-icons/web-development'],
            ['name' => 'Cybersecurity', 'category' => 'Technology & Digital', 'web_icon' => 'https://www.flaticon.com/free-icons/cybersecurity', 'mobile_icon' => 'https://www.flaticon.com/free-icons/cybersecurity'],
            ['name' => 'Gadgets', 'category' => 'Technology & Digital', 'web_icon' => 'https://www.flaticon.com/free-icons/gadget', 'mobile_icon' => 'https://www.flaticon.com/free-icons/gadget'],
            ['name' => 'Gaming Tech', 'category' => 'Technology & Digital', 'web_icon' => 'https://www.flaticon.com/free-icons/joystick', 'mobile_icon' => 'https://www.flaticon.com/free-icons/joystick'],
            ['name' => 'Tech News', 'category' => 'Technology & Digital', 'web_icon' => 'https://www.flaticon.com/free-icons/news', 'mobile_icon' => 'https://www.flaticon.com/free-icons/news'],
            ['name' => 'AR/VR', 'category' => 'Technology & Digital', 'web_icon' => 'https://www.flaticon.com/free-icons/vr', 'mobile_icon' => 'https://www.flaticon.com/free-icons/vr'],
            ['name' => 'Cloud Computing', 'category' => 'Technology & Digital', 'web_icon' => 'https://www.flaticon.com/free-icons/cloud', 'mobile_icon' => 'https://www.flaticon.com/free-icons/cloud'],

            // Additional categories omitted for brevity...
        ];

        foreach ($interests as $interest) {
            DB::table('interests')->insert([
                'name' => $interest['name'],
                'category' => $interest['category'],
                'web_icon' => $interest['web_icon'],
                'mobile_icon' => $interest['mobile_icon'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
