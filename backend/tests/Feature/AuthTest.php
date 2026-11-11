<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_and_receives_token_and_verification_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Reader',
            'email' => 'reader@example.com',
            'password' => 'supersecret',
            'password_confirmation' => 'supersecret',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role', 'abilities']]);

        $this->assertDatabaseHas('users', [
            'email' => 'reader@example.com',
            'role' => 'subscriber',
        ]);

        Notification::assertSentTo(
            User::where('email', 'reader@example.com')->first(),
            VerifyEmailNotification::class
        );
    }

    public function test_registration_respects_the_registration_setting(): void
    {
        \App\Models\Setting::create(['key' => 'registration_enabled', 'value' => '0']);
        \App\Models\Setting::flushCache();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Blocked Reader',
            'email' => 'blocked@example.com',
            'password' => 'supersecret',
            'password_confirmation' => 'supersecret',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'supersecret']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'supersecret',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_banned_users_cannot_login(): void
    {
        $user = User::factory()->banned()->create(['password' => 'supersecret']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'supersecret',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        // Guards are cached inside the test application between requests;
        // reset them to simulate a fresh client.
        $this->app->make('auth')->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_profile_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/profile', [
                'name' => 'Updated Name',
                'bio' => 'Updated bio',
                'current_password' => 'password',
                'password' => 'newsupersecret',
                'password_confirmation' => 'newsupersecret',
            ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated Name');

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('newsupersecret', $user->fresh()->password)
        );
    }

    public function test_password_reset_flow_with_frontend_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        // Simulate the SPA extracting the token from the emailed frontend link.
        $token = null;

        Notification::assertSentTo($user, function (ResetPasswordNotification $notification) use (&$token, $user) {
            $mail = $notification->toMail($user);
            $token = $notification->token;

            $this->assertStringContainsString(
                rtrim(config('app.frontend_url'), '/') . '/reset-password',
                $mail->render()
            );

            return true;
        });

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newsupersecret',
            'password_confirmation' => 'newsupersecret',
        ])->assertOk();

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('newsupersecret', $user->fresh()->password)
        );
    }

    public function test_email_verification_accepts_signed_parameters(): void
    {
        $user = User::factory()->unverified()->create();

        $signedUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        parse_str((string) parse_url($signedUrl, PHP_URL_QUERY), $params);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/email/verify', [
                'id' => $user->id,
                'hash' => sha1($user->email),
                'expires' => $params['expires'],
                'signature' => $params['signature'],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email_verified_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_email_verification_rejects_tampered_signature(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/email/verify', [
                'id' => $user->id,
                'hash' => sha1($user->email),
                'expires' => now()->addMinutes(5)->timestamp,
                'signature' => str_repeat('a', 64),
            ])->assertStatus(403);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'email_verified_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
