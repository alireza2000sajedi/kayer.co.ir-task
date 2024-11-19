<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->createOrFirst(['email' => 'admin@gmail.com'], [
            'name'     => 'admin',
            'password' => Hash::make('12345678'),
            'is_admin' => true
        ]);
    }
}
