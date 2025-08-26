<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Reaction;

class ReactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reactions = [
            [
                'name' => 'go_on',
                'content' => 'Go on, please',
                'image' => 'Reactions/Image/go_on.svg',
                'video' => null,
                'status' => 'active',
                'is_default' => true
            ],
            [
                'name' => 'sad',
                'content' => 'It made me sad',
                'image' => 'Reactions/Image/sad.svg',
                'video' => null,
                'status' => 'active',
                'is_default' => true
            ],
            [
                'name' => 'mad',
                'content' => 'He made me mad',
                'image' => 'Reactions/Image/mad.svg',
                'video' => null,
                'status' => 'active',
                'is_default' => true
            ],
            [
                'name' => 'touched',
                'content' => 'It touched me',
                'image' => 'Reactions/Image/touched.svg',
                'video' => null,
                'status' => 'active',
                'is_default' => true
            ],
            [
                'name' => 'wonderful',
                'content' => 'wonderful',
                'image' => 'Reactions/Image/wonderful.svg',
                'video' => null,
                'status' => 'active',
                'is_default' => true
            ]
        ];

        foreach ($reactions as $reaction) {
            Reaction::updateOrCreate(
                ['name' => $reaction['name']],
                $reaction
            );
        }
    }
}
