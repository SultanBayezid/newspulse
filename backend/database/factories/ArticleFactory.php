<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $publishedAt = fake()->optional(0.8)->dateTimeBetween('-30 days');

        $title = fake()->unique()->sentence(8, true);

        return [
            'user_id' => null, // must be provided by the caller
            'category_id' => null,
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'excerpt' => fake()->paragraph(),
            'body' => '<p>' . fake()->paragraph(6) . '</p>'
                . '<p>' . fake()->paragraph(4) . '</p>'
                . '<h2>' . fake()->sentence(5, true) . '</h2>'
                . '<p>' . fake()->paragraph(5) . '</p>'
                . '<blockquote><p>' . fake()->sentence(12, true) . '</p></blockquote>'
                . '<p>' . fake()->paragraph(4) . '</p>',
            'language' => 'en',
            'status' => ArticleStatus::Published->value,
            'views_count' => fake()->numberBetween(0, 5000),
            'published_at' => $publishedAt,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => ArticleStatus::Draft->value,
            'published_at' => null,
        ]);
    }

    public function review(): static
    {
        return $this->state(fn () => [
            'status' => ArticleStatus::Review->value,
            'published_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => ArticleStatus::Scheduled->value,
            'published_at' => now()->addHours(2),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function breaking(): static
    {
        return $this->state(fn () => ['is_breaking' => true]);
    }
}
