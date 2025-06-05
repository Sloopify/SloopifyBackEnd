<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

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
        ]);


        for ($i = 1; $i <= 20; $i++) {
            User::create([
                'first_name' => 'User' . $i,
                'last_name' => 'Test',
                'email' => 'user' . $i . '@example.com',
                'email_verified_at' => now(),
                'password' => bcrypt('Password123!'),
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'status' => 'active',
                'is_blocked' => 0,
                'age' => rand(18, 40),
                'birthday' => now()->subYears(rand(18, 40))->format('Y-m-d'),
                'phone' => '+9639000000' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'img' => 'https://via.placeholder.com/150',
                'bio' => 'I am user number ' . $i,
                'referral_code' => Str::random(10),
                'referral_link' => 'https://example.com?ref=' . Str::random(6),
                'reffered_by' => null,
                'last_login_at' => now(),
                'country' => 'Syria',
                'city' => 'Damascus',
            ]);
        }
    }
}
