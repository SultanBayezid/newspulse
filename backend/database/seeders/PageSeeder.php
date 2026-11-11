<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $editor = User::where('email', 'editor@newspulse.test')->firstOrFail();

        $pages = [
            [
                'title' => 'About Us',
                'body' => '<h2>Independent by design</h2><p>NewsPulse is a demonstration newsroom built on an open-source publishing platform. Every article, category and widget you see is powered by the same code that runs this site.</p><p>The project exists to show what a modern, API-first newsroom can look like: fast pages, clean editorial workflows and transparent moderation.</p><h2>How we work</h2><p>Stories move from draft to review to publication inside a structured workflow. Editors can schedule coverage, feature the biggest stories and follow reader engagement in real time.</p>',
            ],
            [
                'title' => 'Contact',
                'body' => '<h2>Get in touch</h2><p>News tips, corrections and partnership enquiries are always welcome.</p><ul><li>General: hello@newspulse.test</li><li>Newsroom tips: tips@newspulse.test</li><li>Corrections: corrections@newspulse.test</li></ul><p>We read everything and reply to most messages within two business days.</p>',
            ],
            [
                'title' => 'Privacy Policy',
                'body' => '<h2>What we collect</h2><p>This demo site stores the account information you provide (name, email) and anonymous read analytics per article. We do not sell data and we do not run third-party ad trackers.</p><h2>Newsletter</h2><p>Newsletter subscriptions use a double opt-in flow. Every email contains a one-click unsubscribe link that works immediately.</p><h2>Your rights</h2><p>You can request a copy or deletion of your data at any time by contacting the newsroom.</p>',
            ],
            [
                'title' => 'Terms of Service',
                'body' => '<h2>Using this site</h2><p>Content published here is demo material provided under the MIT-licensed project. You may reuse code freely; please attribute the project when you do.</p><h2>Community standards</h2><p>Comments are moderated. We remove spam, harassment and personal attacks, and we ban repeat offenders.</p>',
            ],
        ];

        foreach ($pages as $page) {
            Page::create([
                'user_id' => $editor->id,
                'title' => $page['title'],
                'slug' => Str::slug($page['title']),
                'body' => $page['body'],
                'is_published' => true,
                'meta_title' => $page['title'] . ' - NewsPulse',
                'meta_description' => Str::limit(strip_tags($page['body']), 160),
            ]);
        }
    }
}
