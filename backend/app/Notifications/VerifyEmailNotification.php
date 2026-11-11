<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Build the verification URL pointing back to the Vue SPA, while keeping
     * the signed parameters generated against the backend route.
     */
    protected function verificationUrl($notifiable): string
    {
        $signedUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        $query = parse_url($signedUrl, PHP_URL_QUERY) ?: '';
        $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');

        return $frontend . '/verify-email?' . $query;
    }
}
