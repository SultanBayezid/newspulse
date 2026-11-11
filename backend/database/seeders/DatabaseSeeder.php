<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a complete demo newsroom.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ArticleSeeder::class,
            PageSeeder::class,
            PollSeeder::class,
            SubscriberSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
