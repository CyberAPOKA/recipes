<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Christian Steffens',
            'email' => 'oficialsteffens@hotmail.com',
            'password' => Hash::make('123123123'),
        ]);

        User::create([
            'name' => 'Ana Clara',
            'email' => 'anaclara@hotmail.com',
            'password' => Hash::make('123123123'),
        ]);

        User::create([
            'name' => 'Júlio',
            'email' => 'julio@hotmail.com',
            'password' => Hash::make('123123123'),
        ]);

        for ($i = 0; $i < 10; $i++) {
            User::create([
                'name' => fake()->name(),
                'email' => fake()->email(),
                'password' => Hash::make('123123123'),
            ]);
        }
    }
}
