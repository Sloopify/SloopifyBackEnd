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
                'image' => null,
                'video' => null,
                'status' => 'active',
                'is_default' => true
            ],
            [
                'name' => 'sad',
                'content' => 'It made me sad',
                'image' => null,
                'video' => null,
                'status' => 'active',
                'is_default' => true
            ],
            [
                'name' => 'mad',
                'content' => 'He made me mad',
                'image' => null,
                'video' => null,
                'status' => 'active',
                'is_default' => true
            ],
            [
                'name' => 'touched',
                'content' => 'It touched me',
                'image' => null,
                'video' => null,
                'status' => 'active',
                'is_default' => true
            ],
            [
                'name' => 'wonderful',
                'content' => 'wonderful',
                'image' => null,
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
