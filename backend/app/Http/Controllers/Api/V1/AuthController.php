<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\Settings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly Settings $settings)
    {
    }

    /**
     * Create a new reader account.
     */
    public function register(Request $request): JsonResponse
    {
        if (! $this->settings->bool('registration_enabled', true)) {
            return response()->json([
                'message' => 'Registration is currently disabled.',
            ], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            ...$data,
            'role' => 'subscriber',
        ]);

        event(new Registered($user));

        return response()->json([
            'token' => $user->createToken('spa')->plainTextToken,
            'user' => new UserResource($user),
            'message' => 'Account created successfully. Please check your inbox to verify your email address.',
        ], 201);
    }

    /**
     * Issue a personal access token for valid credentials.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if ($user->isBanned()) {
            throw ValidationException::withMessages([
                'email' => 'This account has been suspended.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        return response()->json([
            'token' => $user->createToken('spa')->plainTextToken,
            'user' => new UserResource($user),
            'message' => 'Signed in successfully.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out successfully.']);
    }

    public function me(Request $request): JsonResource
    {
        return new UserResource($request->user());
    }

    public function updateProfile(ProfileUpdateRequest $request): JsonResource
    {
        $user = $request->user();
        $data = $request->validated();

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->fill(collect($data)->except(['password', 'current_password'])->all());
        $user->save();

        return new UserResource($user->fresh());
    }

    /**
     * Send a password reset link to the given email.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Always answer with success so we never leak which emails exist.
        return response()->json([
            'message' => 'If an account exists for that email, a reset link has been sent.',
        ]);
    }

    /**
     * Reset the password using a valid token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $credentials,
            function (User $user, string $password) {
                $user->password = $password;
                $user->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Your password has been reset. You can now sign in.',
        ]);
    }

    /**
     * Re-send the email verification link.
     */
    public function sendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Your email address is already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'A fresh verification link has been sent to your email address.',
        ]);
    }

    /**
     * Verify an email address using signed parameters passed through the SPA.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'hash' => ['required', 'string'],
            'expires' => ['required', 'integer'],
            'signature' => ['required', 'string'],
        ]);

        $reconstructed = url('/api/v1/auth/email/verify/' . $data['id'] . '/' . $data['hash'])
            . '?expires=' . $data['expires'] . '&signature=' . urlencode($data['signature']);

        if (! URL::hasValidSignature(\Illuminate\Http\Request::create($reconstructed))) {
            return response()->json([
                'message' => 'This verification link is invalid or has expired.',
            ], 403);
        }

        $user = User::findOrFail($data['id']);

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $data['hash'])) {
            return response()->json([
                'message' => 'This verification link is invalid.',
            ], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return response()->json([
            'message' => 'Your email address has been verified.',
            'user' => new UserResource($user->fresh()),
        ]);
    }
}
