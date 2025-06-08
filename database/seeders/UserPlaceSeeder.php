<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UserPlace;

class UserPlaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        UserPlace::create([
            'user_id' => 1,
            'name' => 'Home',
            'city' => 'New York',
            'country' => 'USA',
            'latitude' => '40.7128',
            'longitude' => '-74.0060',
            'status' => 'active',
        ]);

        UserPlace::create([
            'user_id' => 1,
            'name' => 'Work',
            'city' => 'New York',
            'country' => 'USA',
            'latitude' => '40.7128',
            'longitude' => '-74.0060',
            'status' => 'active',
        ]);
    }
}
