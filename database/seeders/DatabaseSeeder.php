<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'name' => 'Usuario Principal',
            'email' => 'admin@example.com',
        ]);

        $this->call([
            TopicSeeder::class,
            ArticleSeeder::class,
        ]);
    }
}
