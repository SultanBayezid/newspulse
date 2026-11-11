<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Notifications\CommentSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $reader;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $author = User::factory()->role(\App\Enums\UserRole::Author)->create();
        $this->editor = User::factory()->role(\App\Enums\UserRole::Editor)->create();
        $this->reader = User::factory()->create();

        $category = Category::create(['name' => 'Health', 'slug' => 'health', 'is_active' => true]);
        $this->article = Article::factory()->for($author, 'author')->for($category)->create([
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);
    }

    public function test_guests_cannot_comment(): void
    {
        $this->postJson('/api/v1/comments', [
            'article_id' => $this->article->id,
            'body' => 'Nice read!',
        ])->assertUnauthorized();
    }

    public function test_comments_wait_for_moderation_by_default(): void
    {
        Notification::fake();

        $this->actingAs($this->reader, 'sanctum')
            ->postJson('/api/v1/comments', [
                'article_id' => $this->article->id,
                'body' => 'Great piece, thanks for reporting this.',
            ])->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        Notification::assertSentTo($this->editor, CommentSubmitted::class);

        // Pending comments are not public.
        $this->getJson("/api/v1/articles/{$this->article->id}/comments")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_comments_auto_approve_when_moderation_is_disabled(): void
    {
        \App\Models\Setting::create(['key' => 'comments_moderation', 'value' => '0']);
        \App\Models\Setting::flushCache();

        $this->actingAs($this->reader, 'sanctum')
            ->postJson('/api/v1/comments', [
                'article_id' => $this->article->id,
                'body' => 'Instant comment.',
            ])->assertCreated()
            ->assertJsonPath('data.status', 'approved');

        $this->getJson("/api/v1/articles/{$this->article->id}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_editor_can_approve_and_reject_comments(): void
    {
        $comment = $this->article->comments()->create([
            'user_id' => $this->reader->id,
            'body' => 'Pending comment',
            'status' => 'pending',
        ]);

        $this->actingAs($this->editor, 'sanctum')
            ->postJson("/api/v1/admin/comments/{$comment->id}/status", ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->actingAs($this->editor, 'sanctum')
            ->postJson("/api/v1/admin/comments/{$comment->id}/status", ['status' => 'rejected'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->actingAs($this->editor, 'sanctum')
            ->deleteJson("/api/v1/admin/comments/{$comment->id}")
            ->assertNoContent();
    }

    public function test_replies_are_threaded(): void
    {
        $root = $this->article->comments()->create([
            'user_id' => $this->reader->id,
            'body' => 'Root comment',
            'status' => 'approved',
        ]);

        $this->article->comments()->create([
            'user_id' => $this->reader->id,
            'parent_id' => $root->id,
            'body' => 'Reply',
            'status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/articles/{$this->article->id}/comments");

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Root comment', $response->json('data.0.body'));
        $this->assertCount(1, $response->json('data.0.replies'));
    }

    public function test_reactions_toggle_on_articles_and_comments(): void
    {
        $comment = $this->article->comments()->create([
            'user_id' => $this->reader->id,
            'body' => 'Likeable comment',
            'status' => 'approved',
        ]);

        // Like an article.
        $this->actingAs($this->reader, 'sanctum')
            ->postJson('/api/v1/reactions/toggle', ['type' => 'article', 'id' => $this->article->id])
            ->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('likes_count', 1);

        // Unlike it again.
        $this->actingAs($this->reader, 'sanctum')
            ->postJson('/api/v1/reactions/toggle', ['type' => 'article', 'id' => $this->article->id])
            ->assertOk()
            ->assertJsonPath('liked', false)
            ->assertJsonPath('likes_count', 0);

        // Like a comment.
        $this->actingAs($this->reader, 'sanctum')
            ->postJson('/api/v1/reactions/toggle', ['type' => 'comment', 'id' => $comment->id])
            ->assertOk()
            ->assertJsonPath('liked', true);
    }
}
