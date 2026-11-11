<?php

namespace Database\Seeders;

use App\Models\NewsletterSubscriber;
use Illuminate\Database\Seeder;

class SubscriberSeeder extends Seeder
{
    public function run(): void
    {
        $subscribers = [
            ['email' => 'reader.one@example.com', 'name' => 'Ayesha', 'status' => 'subscribed'],
            ['email' => 'reader.two@example.com', 'name' => 'Kabir', 'status' => 'subscribed'],
            ['email' => 'reader.three@example.com', 'name' => 'Mim', 'status' => 'pending'],
            ['email' => 'reader.four@example.com', 'name' => null, 'status' => 'unsubscribed'],
        ];

        foreach ($subscribers as $subscriber) {
            NewsletterSubscriber::create([
                ...$subscriber,
                'token' => bin2hex(random_bytes(16)),
                'subscribed_at' => $subscriber['status'] === 'subscribed' ? now()->subDays(3) : null,
                'unsubscribed_at' => $subscriber['status'] === 'unsubscribed' ? now()->subDay() : null,
            ]);
        }
    }
}
