<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'World', 'color' => '#0ea5e9', 'description' => 'Global affairs, diplomacy and international developments.'],
            ['name' => 'Politics', 'color' => '#8b5cf6', 'description' => 'Elections, policy and the people who shape them.'],
            ['name' => 'Business', 'color' => '#10b981', 'description' => 'Markets, companies and the economy explained.'],
            ['name' => 'Technology', 'color' => '#f59e0b', 'description' => 'Software, gadgets, startups and the science behind them.'],
            ['name' => 'Sports', 'color' => '#ef4444', 'description' => 'Cricket, football and the biggest stories on the field.'],
            ['name' => 'Entertainment', 'color' => '#ec4899', 'description' => 'Film, music, streaming and celebrity culture.'],
            ['name' => 'Health', 'color' => '#14b8a6', 'description' => 'Medicine, wellness and public health reporting.'],
            ['name' => 'Education', 'color' => '#6366f1', 'description' => 'Schools, universities and lifelong learning.'],
        ];

        foreach ($categories as $index => $category) {
            Category::create([
                ...$category,
                'slug' => \Illuminate\Support\Str::slug($category['name']),
                'position' => $index + 1,
                'is_active' => true,
            ]);
        }
    }
}
