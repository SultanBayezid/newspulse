<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    private User $reader;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $author = User::factory()->role(\App\Enums\UserRole::Author)->create();
        $this->reader = User::factory()->create();

        $category = Category::create(['name' => 'Business', 'slug' => 'business', 'is_active' => true]);
        $this->article = Article::factory()->for($author, 'author')->for($category)->create([
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);
    }

    public function test_bookmarks_toggle_and_list(): void
    {
        // Add.
        $this->actingAs($this->reader, 'sanctum')
            ->postJson('/api/v1/bookmarks/toggle', ['article_id' => $this->article->id])
            ->assertOk()
            ->assertJsonPath('bookmarked', true);

        // Appears in the bookmark list.
        $this->actingAs($this->reader, 'sanctum')
            ->getJson('/api/v1/bookmarks')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Remove.
        $this->actingAs($this->reader, 'sanctum')
            ->postJson('/api/v1/bookmarks/toggle', ['article_id' => $this->article->id])
            ->assertOk()
            ->assertJsonPath('bookmarked', false);

        $this->actingAs($this->reader, 'sanctum')
            ->getJson('/api/v1/bookmarks')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_bookmarks_require_authentication(): void
    {
        $this->getJson('/api/v1/bookmarks')->assertUnauthorized();
    }

    public function test_poll_voting_and_results(): void
    {
        $poll = \App\Models\Poll::create([
            'question' => 'Best section?',
            'is_active' => true,
        ]);

        $optionA = $poll->options()->create(['label' => 'Tech', 'position' => 0]);
        $optionB = $poll->options()->create(['label' => 'Sports', 'position' => 1]);

        // Public endpoint shows the poll.
        $this->getJson('/api/v1/polls/active')
            ->assertOk()
            ->assertJsonPath('data.question', 'Best section?');

        // Guests cannot vote.
        $this->postJson("/api/v1/polls/{$poll->id}/vote", ['poll_option_id' => $optionA->id])
            ->assertUnauthorized();

        // Reader votes.
        $this->actingAs($this->reader, 'sanctum')
            ->postJson("/api/v1/polls/{$poll->id}/vote", ['poll_option_id' => $optionA->id])
            ->assertOk()
            ->assertJsonPath('data.has_voted', true)
            ->assertJsonPath('data.total_votes', 1)
            ->assertJsonPath('data.options.0.percent', 100);

        // Second vote is rejected.
        $this->actingAs($this->reader, 'sanctum')
            ->postJson("/api/v1/polls/{$poll->id}/vote", ['poll_option_id' => $optionB->id])
            ->assertStatus(422);
    }

    public function test_newsletter_double_opt_in_flow(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        // 1. Subscribe - pending.
        \Illuminate\Support\Facades\Mail::assertNothingSent();

        $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'fan@example.com',
        ])->assertOk();

        $subscriber = \App\Models\NewsletterSubscriber::where('email', 'fan@example.com')->firstOrFail();

        $this->assertSame('pending', $subscriber->status);
        $this->assertNotNull($subscriber->token);

        \Illuminate\Support\Facades\Mail::assertSent(
            \App\Mail\NewsletterConfirmation::class,
            fn ($mail) => $mail->subscriber->is($subscriber)
        );

        // 2. Duplicate while pending - still OK (resends confirmation).
        $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'fan@example.com',
        ])->assertOk();

        // 3. Confirm via token.
        $this->postJson("/api/v1/newsletter/confirm/{$subscriber->token}")
            ->assertOk();

        $this->assertSame('subscribed', $subscriber->fresh()->status);

        // 4. Subscribed duplicate is rejected.
        $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'fan@example.com',
        ])->assertStatus(422);

        // 5. Unsubscribe.
        $this->postJson("/api/v1/newsletter/unsubscribe/{$subscriber->token}")
            ->assertOk();

        $this->assertSame('unsubscribed', $subscriber->fresh()->status);
    }

    public function test_notifications_endpoints(): void
    {
        $this->article->author->notify(
            new \App\Notifications\ArticleStatusChanged($this->article, 'approved', null)
        );

        $this->actingAs($this->article->author, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $response = $this->actingAs($this->article->author, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertOk()->assertJsonCount(1, 'data');
        $notificationId = $response->json('data.0.id');

        $this->actingAs($this->article->author, 'sanctum')
            ->postJson("/api/v1/notifications/{$notificationId}/read")
            ->assertOk();

        $this->actingAs($this->article->author, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }
}
