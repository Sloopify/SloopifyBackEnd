<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        User::create([
        'first_name' => 'ebrahem',
        'last_name' => 'alwish',
        'email' => 'ebrahemk968@gmail.com',
        'email_verified_at' => now(),
        'password' => bcrypt('Password123!'),
        'gender' => 'male',
        'status' => 'active',
        'is_blocked' => 0,
        'age' => 25,
        'birthday' => '1996-01-01',
        'phone' => '+963997482515',
        'img' => 'https://via.placeholder.com/150',
        'bio' => 'I am a developer',
        'referral_code' => '1234567890',
        'referral_link' => 'https://www.google.com',
        'reffered_by' => '1234567890',
        'last_login_at' => now(),
        'country' => 'Syria',
        'city' => 'Damascus',
        'device_id' => '1234567890',
        'device_type' => 'web',
        ]);
    }
}
