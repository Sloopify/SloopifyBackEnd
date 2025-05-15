<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        Permission::create([
            'name' => 'admin.admin.index',
            'description' => 'Admin Index',
            'slug' => 'admin.admin.index',
            'type' => 'admin',
            'is_active' => 1,
        ]);
    }
}
