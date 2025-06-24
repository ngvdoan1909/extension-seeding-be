<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        $users = [
            [
                'user_id' => \Str::uuid()->toString(),
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => \Illuminate\Support\Facades\Hash::make('6669996789'),
                'point' => 10000000000,
                'role' => 1
            ],
            [
                'user_id' => \Str::uuid()->toString(),
                'name' => 'test1',
                'email' => 'test1@gmail.com',
                'password' => \Illuminate\Support\Facades\Hash::make('123456789'),
                'point' => 5000000,
                'role' => 2
            ]
        ];

        foreach ($users as $user) {
            \App\Models\User::factory()->create($user);
        }
    }
}
