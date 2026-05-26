<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@linodecloud.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'balance' => 50000000,
                'is_admin' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'demo@linodecloud.local'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'balance' => 2000000,
                'is_admin' => false,
            ]
        );
    }
}
