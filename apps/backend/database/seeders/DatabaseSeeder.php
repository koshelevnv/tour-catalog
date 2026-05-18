<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_DEFAULT_EMAIL', 'admin@example.com')],
            ['name' => 'Admin', 'password' => bcrypt(env('ADMIN_DEFAULT_PASSWORD', 'admin_secret'))],
        );

        $this->call(TourSeeder::class);
    }
}
