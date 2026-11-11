<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Notifications\ArticleStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ArticleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $author;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->role(\App\Enums\UserRole::Editor)->create();
        $this->author = User::factory()->role(\App\Enums\UserRole::Author)->create();
        $this->category = Category::create(['name' => 'World', 'slug' => 'world', 'is_active' => true]);
    }

    public function test_author_can_create_a_draft(): void
    {
        $response = $this->actingAs($this->author, 'sanctum')
            ->postJson('/api/v1/admin/articles', [
                'title' => 'Field notes from the flood zone',
                'body' => '<p>Dispatch from the delta region.</p>',
                'category_id' => $this->category->id,
                'tags' => ['Climate', 'Rivers'],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.author.name', $this->author->name);

        $this->assertDatabaseHas('articles', [
            'title' => 'Field notes from the flood zone',
            'status' => 'draft',
            'user_id' => $this->author->id,
        ]);
    }

    public function test_author_cannot_self_publish(): void
    {
        $article = Article::factory()->for($this->author, 'author')->for($this->category)->draft()->create();

        $this->actingAs($this->author, 'sanctum')
            ->postJson("/api/v1/admin/articles/{$article->id}/status", [
                'status' => 'published',
            ])->assertStatus(422);
    }

    public function test_full_editorial_workflow_with_notifications(): void
    {
        Notification::fake();

        $article = Article::factory()->for($this->author, 'author')->for($this->category)->draft()->create();

        // 1. Author submits for review.
        $this->actingAs($this->author, 'sanctum')
            ->postJson("/api/v1/admin/articles/{$article->id}/status", ['status' => 'review'])
            ->assertOk()
            ->assertJsonPath('data.status', 'review');

        Notification::assertSentTo($this->editor, ArticleStatusChanged::class);

        // 2. Editor rejects with a note.
        $this->actingAs($this->editor, 'sanctum')
            ->postJson("/api/v1/admin/articles/{$article->id}/status", [
                'status' => 'draft',
                'review_note' => 'Needs a second source.',
            ])->assertOk()->assertJsonPath('data.status', 'draft');

        Notification::assertSentTo($this->author, function (ArticleStatusChanged $notification) {
            return $notification->action === 'rejected'
                && $notification->note === 'Needs a second source.';
        });

        // 3. Author resubmits, editor publishes.
        $this->actingAs($this->author, 'sanctum')
            ->postJson("/api/v1/admin/articles/{$article->id}/status", ['status' => 'review'])
            ->assertOk();

        $this->actingAs($this->editor, 'sanctum')
            ->postJson("/api/v1/admin/articles/{$article->id}/status", ['status' => 'published'])
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->assertNotNull($article->fresh()->published_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'article.published']);

        // Published article is now publicly visible.
        $this->getJson('/api/v1/articles')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_scheduling_requires_a_future_date(): void
    {
        $article = Article::factory()->for($this->author, 'author')->for($this->category)->draft()->create();

        $this->actingAs($this->editor, 'sanctum')
            ->postJson("/api/v1/admin/articles/{$article->id}/status", [
                'status' => 'scheduled',
                'published_at' => now()->subHour()->toDateTimeString(),
            ])->assertStatus(422)->assertJsonValidationErrors('published_at');
    }

    public function test_scheduled_article_publishes_via_artisan_command(): void
    {
        $article = Article::factory()->for($this->author, 'author')->for($this->category)->create([
            'status' => 'scheduled',
            'published_at' => now()->subMinute(),
        ]);

        $this->artisan('articles:publish')->assertSuccessful();

        $this->assertSame('published', $article->fresh()->status->value);
    }

    public function test_authors_cannot_edit_foreign_articles(): void
    {
        $otherAuthor = User::factory()->role(\App\Enums\UserRole::Author)->create();
        $article = Article::factory()->for($otherAuthor, 'author')->for($this->category)->draft()->create();

        $this->actingAs($this->author, 'sanctum')
            ->putJson("/api/v1/admin/articles/{$article->id}", [
                'title' => 'Hijacked title',
                'body' => '<p>Nope</p>',
            ])->assertStatus(403);
    }

    public function test_editors_can_edit_any_article_and_manage_flags(): void
    {
        $article = Article::factory()->for($this->author, 'author')->for($this->category)->draft()->create();

        $this->actingAs($this->editor, 'sanctum')
            ->putJson("/api/v1/admin/articles/{$article->id}", [
                'title' => 'Editorial rewrite',
                'body' => '<p>Improved copy.</p>',
                'is_featured' => true,
                'status' => 'published',
                'published_at' => now()->toDateTimeString(),
            ])->assertOk()
            ->assertJsonPath('data.title', 'Editorial rewrite')
            ->assertJsonPath('data.is_featured', true)
            ->assertJsonPath('data.status', 'published');
    }

    public function test_author_dashboard_listing_is_scoped_to_own_articles(): void
    {
        Article::factory()->for($this->author, 'author')->for($this->category)->draft()->create();
        Article::factory()->count(2)->for(User::factory()->role(\App\Enums\UserRole::Author)->create(), 'author')
            ->for($this->category)->draft()->create();

        $response = $this->actingAs($this->author, 'sanctum')
            ->getJson('/api/v1/admin/articles');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_subscribers_cannot_access_the_admin_workspace(): void
    {
        $subscriber = User::factory()->create();

        $this->actingAs($subscriber, 'sanctum')
            ->getJson('/api/v1/admin/articles')
            ->assertStatus(403);
    }
}
