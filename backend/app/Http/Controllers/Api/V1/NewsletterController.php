<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\NewsletterSubscribeRequest;
use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    /**
     * Start (or restart) a subscription - double opt-in.
     */
    public function subscribe(NewsletterSubscribeRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->input('email')));

        $subscriber = NewsletterSubscriber::query()->where('email', $email)->first();

        if ($subscriber && $subscriber->status === NewsletterSubscriber::STATUS_SUBSCRIBED) {
            return response()->json([
                'message' => 'This email address is already subscribed to the newsletter.',
            ], 422);
        }

        if ($subscriber) {
            // Resend the confirmation for pending or previously unsubscribed
            // readers. A fresh token is only minted when coming back from
            // "unsubscribed", so pending links stay valid.
            $subscriber->forceFill([
                'token' => $subscriber->status === NewsletterSubscriber::STATUS_UNSUBSCRIBED
                    ? bin2hex(random_bytes(16))
                    : $subscriber->token,
                'status' => NewsletterSubscriber::STATUS_PENDING,
                'unsubscribed_at' => null,
            ])->save();
        } else {
            $subscriber = NewsletterSubscriber::create([
                'email' => $email,
                'name' => $request->input('name'),
                'status' => NewsletterSubscriber::STATUS_PENDING,
            ]);
        }

        Mail::to($subscriber->email)->send(new NewsletterConfirmation($subscriber));

        return response()->json([
            'message' => 'Almost there! Please check your inbox and confirm your subscription.',
        ]);
    }

    /**
     * Confirm a subscription using the emailed token.
     */
    public function confirm(string $token): JsonResponse
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->first();

        abort_unless($subscriber !== null, 404, 'This confirmation link is invalid.');

        if ($subscriber->status !== NewsletterSubscriber::STATUS_SUBSCRIBED) {
            $subscriber->forceFill([
                'status' => NewsletterSubscriber::STATUS_SUBSCRIBED,
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ])->save();
        }

        return response()->json([
            'message' => 'Your subscription is confirmed. Welcome aboard!',
        ]);
    }

    /**
     * Opt out of the newsletter.
     */
    public function unsubscribe(string $token): JsonResponse
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->first();

        abort_unless($subscriber !== null, 404, 'This unsubscribe link is invalid.');

        if ($subscriber->status !== NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
            $subscriber->forceFill([
                'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
                'unsubscribed_at' => now(),
            ])->save();
        }

        return response()->json([
            'message' => 'You have been unsubscribed from the newsletter.',
        ]);
    }
}
