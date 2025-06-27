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
        $admin_id = \Str::uuid()->toString();
        $user_test_id = \Str::uuid()->toString();
        $users = [
            [
                'user_id' => $admin_id,
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => \Illuminate\Support\Facades\Hash::make('1'),
                'role' => 1
            ],
            [
                'user_id' => $user_test_id,
                'name' => 'cr7',
                'email' => 'quangtrunghytq203@gmail.com',
                'password' => \Illuminate\Support\Facades\Hash::make('1'),
                'role' => 2
            ]
        ];

        foreach ($users as $user) {
            \App\Models\User::factory()->create($user);
        }

        \App\Models\Deposit::create([
            'user_id' => $admin_id,
            'id_transaction' => 'D_' . \Str::random(8),
            'from' => null,
            'note' => 'admin chuyển',
            'amount' => 1000000000,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);
    }
}
