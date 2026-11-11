<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->role(\App\Enums\UserRole::SuperAdmin)->create();
        $this->editor = User::factory()->role(\App\Enums\UserRole::Editor)->create();
    }

    public function test_dashboard_returns_totals_and_charts(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard');

        $response->assertOk();

        $data = $response->json('data');

        $this->assertArrayHasKey('totals', $data);
        $this->assertArrayHasKey('charts', $data);
        $this->assertArrayHasKey('views', $data['charts']);
        $this->assertCount(30, $data['charts']['views']);
        $this->assertSame('super_admin', $data['role']);
    }

    public function test_editors_manage_categories_and_tags(): void
    {
        // Create category.
        $response = $this->actingAs($this->editor, 'sanctum')
            ->postJson('/api/v1/admin/categories', [
                'name' => 'Culture',
                'description' => 'Arts and ideas',
                'color' => '#ec4899',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'culture')
            ->assertJsonPath('data.is_active', true);

        $categoryId = $response->json('data.id');

        // Update it.
        $this->actingAs($this->editor, 'sanctum')
            ->putJson("/api/v1/admin/categories/{$categoryId}", [
                'name' => 'Culture & Ideas',
            ])->assertOk()->assertJsonPath('data.name', 'Culture & Ideas');

        // Delete it.
        $this->actingAs($this->editor, 'sanctum')
            ->deleteJson("/api/v1/admin/categories/{$categoryId}")
            ->assertNoContent();

        // Tag CRUD.
        $tag = $this->actingAs($this->editor, 'sanctum')
            ->postJson('/api/v1/admin/tags', ['name' => 'Festivals']);

        $tag->assertCreated();

        $this->actingAs($this->editor, 'sanctum')
            ->deleteJson('/api/v1/admin/tags/' . $tag->json('data.id'))
            ->assertNoContent();
    }

    public function test_authors_cannot_manage_categories(): void
    {
        $author = User::factory()->role(\App\Enums\UserRole::Author)->create();

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/admin/categories', ['name' => 'Nope'])
            ->assertStatus(403);
    }

    public function test_duplicate_category_slugs_are_rejected(): void
    {
        $this->actingAs($this->editor, 'sanctum')
            ->postJson('/api/v1/admin/categories', ['name' => 'Sports'])
            ->assertCreated();

        $this->actingAs($this->editor, 'sanctum')
            ->postJson('/api/v1/admin/categories', ['name' => 'Other', 'slug' => 'sports'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    public function test_editors_manage_pages(): void
    {
        $response = $this->actingAs($this->editor, 'sanctum')
            ->postJson('/api/v1/admin/pages', [
                'title' => 'Editorial Standards',
                'body' => '<h2>Our standards</h2><p>Accuracy first.</p>',
            ]);

        $response->assertCreated()->assertJsonPath('data.slug', 'editorial-standards');

        $slug = $response->json('data.slug');

        // Visible publicly.
        $this->getJson("/api/v1/pages/{$slug}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Editorial Standards');
    }

    public function test_page_body_is_sanitized(): void
    {
        $this->actingAs($this->editor, 'sanctum')
            ->postJson('/api/v1/admin/pages', [
                'title' => 'Security',
                'body' => '<p onclick="evil()">Text</p><script>alert(1)</script>',
            ])->assertCreated();

        $this->getJson('/api/v1/pages/security')
            ->assertOk()
            ->assertJsonPath('data.body', '<p>Text</p>');
    }

    public function test_admin_manages_users_and_bans(): void
    {
        $target = User::factory()->create();

        // Editor cannot manage users.
        $this->actingAs($this->editor, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403);

        // Admin lists users (admin, editor and the target).
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        // Ban.
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$target->id}/ban")
            ->assertOk()
            ->assertJsonPath('data.is_banned', true);

        // Banned user can no longer sign in.
        $this->postJson('/api/v1/auth/login', [
            'email' => $target->email,
            'password' => 'password',
        ])->assertStatus(422);

        // Unban.
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$target->id}/unban")
            ->assertOk()
            ->assertJsonPath('data.is_banned', false);

        // Admin cannot ban themselves.
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$this->admin->id}/ban")
            ->assertStatus(403);
    }

    public function test_last_super_admin_cannot_be_demoted(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/users/{$this->admin->id}", [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'editor',
            ])->assertStatus(422)->assertJsonValidationErrors('role');
    }

    public function test_settings_update_flow(): void
    {
        // Public settings are visible.
        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('data.site_name', 'NewsPulse');

        // Editor cannot edit settings.
        $this->actingAs($this->editor, 'sanctum')
            ->putJson('/api/v1/admin/settings', ['settings' => ['site_name' => 'Nope']])
            ->assertStatus(403);

        // Admin edits settings.
        $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/admin/settings', [
                'settings' => [
                    'site_name' => 'The Morning Pulse',
                    'site_tagline' => 'All the news before breakfast',
                ],
            ])->assertOk();

        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('data.site_name', 'The Morning Pulse');
    }

    public function test_subscriber_export_streams_csv(): void
    {
        \App\Models\NewsletterSubscriber::create([
            'email' => 'csv@example.com',
            'status' => 'subscribed',
            'subscribed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/v1/admin/newsletter/subscribers/export');

        $response->assertOk();
        $this->assertStringContainsString('csv@example.com', $response->streamedContent());
    }

    public function test_activity_log_requires_editorial_role(): void
    {
        $author = User::factory()->role(\App\Enums\UserRole::Author)->create();

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/admin/activity')
            ->assertStatus(403);

        $this->actingAs($this->editor, 'sanctum')
            ->getJson('/api/v1/admin/activity')
            ->assertOk();
    }
}
