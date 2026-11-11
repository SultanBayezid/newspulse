<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'Elections', 'Climate', 'Startups', 'Robotics', 'Cricket', 'Football',
            'Markets', 'Mobile', 'Gaming', 'Space', 'Research', 'Policy',
            'Streaming', 'Music', 'Travel', 'Food',
        ];

        foreach ($tags as $tag) {
            Tag::create([
                'name' => $tag,
                'slug' => \Illuminate\Support\Str::slug($tag),
            ]);
        }
    }
}
