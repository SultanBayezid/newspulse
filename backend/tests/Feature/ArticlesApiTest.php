<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlesApiTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->role(\App\Enums\UserRole::Author)->create();
        $this->category = Category::create([
            'name' => 'Technology',
            'slug' => 'technology',
            'color' => '#f59e0b',
            'is_active' => true,
        ]);
    }

    public function test_guests_only_see_published_articles(): void
    {
        $published = Article::factory()->for($this->author, 'author')->for($this->category)->create([
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        Article::factory()->for($this->author, 'author')->for($this->category)->draft()->create();

        $response = $this->getJson('/api/v1/articles');

        $response->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($published->id, $response->json('data.0.id'));
    }

    public function test_scheduled_articles_stay_hidden_until_their_time(): void
    {
        Article::factory()->for($this->author, 'author')->for($this->category)->scheduled()->create([
            'published_at' => now()->addDay(),
        ]);

        $this->getJson('/api/v1/articles')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_article_detail_increments_views(): void
    {
        $article = Article::factory()->for($this->author, 'author')->for($this->category)->create([
            'status' => 'published',
            'published_at' => now()->subHour(),
            'views_count' => 0,
        ]);

        $this->getJson("/api/v1/articles/{$article->slug}")
            ->assertOk()
            ->assertJsonPath('data.views_count', 1);

        $this->assertDatabaseHas('article_views', [
            'article_id' => $article->id,
            'viewed_on' => now()->toDateString(),
            'count' => 1,
        ]);
    }

    public function test_draft_articles_return_404_for_guests(): void
    {
        $article = Article::factory()->for($this->author, 'author')->for($this->category)->draft()->create();

        $this->getJson("/api/v1/articles/{$article->slug}")->assertNotFound();
    }

    public function test_articles_can_be_filtered_by_category_tag_and_language(): void
    {
        $otherCategory = Category::create(['name' => 'Sports', 'slug' => 'sports', 'is_active' => true]);
        $tag = Tag::create(['name' => 'Cricket', 'slug' => 'cricket']);

        $first = Article::factory()->for($this->author, 'author')->for($this->category)->create([
            'status' => 'published',
            'published_at' => now()->subHour(),
            'language' => 'en',
        ]);
        $first->tags()->attach($tag);

        Article::factory()->for($this->author, 'author')->for($otherCategory)->create([
            'status' => 'published',
            'published_at' => now()->subHour(),
            'language' => 'bn',
        ]);

        $this->getJson('/api/v1/articles?category=technology')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/articles?tag=cricket')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/articles?language=bn')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_search_finds_matching_articles(): void
    {
        Article::factory()->for($this->author, 'author')->for($this->category)->create([
            'title' => 'Open-source models close the gap',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        Article::factory()->for($this->author, 'author')->for($this->category)->create([
            'title' => 'Unrelated headline about traffic',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $this->getJson('/api/v1/articles?q=open-source models')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_breaking_feed_lists_only_recent_breaking_news(): void
    {
        Article::factory()->for($this->author, 'author')->for($this->category)->breaking()->create([
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        Article::factory()->for($this->author, 'author')->for($this->category)->breaking()->create([
            'status' => 'published',
            'published_at' => now()->subDays(2),
        ]);

        $this->getJson('/api/v1/articles/breaking')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_trending_uses_recent_view_counts(): void
    {
        $hot = Article::factory()->for($this->author, 'author')->for($this->category)->create([
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        \Illuminate\Support\Facades\DB::table('article_views')->insert([
            'article_id' => $hot->id,
            'viewed_on' => now()->toDateString(),
            'count' => 500,
        ]);

        $response = $this->getJson('/api/v1/articles/trending');

        $response->assertOk();
        $this->assertSame($hot->id, $response->json('data.0.id'));
    }
}
