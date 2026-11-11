<?php

namespace Database\Seeders;

use App\Models\Poll;
use App\Models\User;
use Illuminate\Database\Seeder;

class PollSeeder extends Seeder
{
    public function run(): void
    {
        $poll = Poll::create([
            'question' => 'Which section should we cover more deeply?',
            'description' => 'Help the newsroom decide where to invest in long-form reporting next quarter.',
            'is_active' => true,
            'ends_at' => now()->addDays(7),
        ]);

        $options = [
            'Investigations', 'Technology', 'Sports', 'Culture',
        ];

        foreach ($options as $index => $label) {
            $poll->options()->create([
                'label' => $label,
                'position' => $index,
                'votes_count' => 0,
            ]);
        }

        // A few demo votes from the seeded accounts.
        $voters = User::whereIn('email', [
            'admin@newspulse.test', 'editor@newspulse.test',
            'author@newspulse.test', 'farhana@newspulse.test', 'reader@newspulse.test',
        ])->get();

        $voteMap = [0, 1, 1, 2, 3];

        foreach ($voters as $index => $voter) {
            $optionId = $poll->options()->pluck('id')[$voteMap[$index]];

            $poll->votes()->create([
                'user_id' => $voter->id,
                'poll_option_id' => $optionId,
            ]);

            \App\Models\PollOption::whereKey($optionId)->increment('votes_count');
        }
    }
}
