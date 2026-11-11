<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalVotes = $this->total_votes;
        $user = $request->user();

        $userVote = $user
            ? $this->votes->firstWhere('user_id', $user->id)?->poll_option_id
            : null;

        return [
            'id' => $this->id,
            'question' => $this->question,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'ends_at' => $this->ends_at?->toISOString(),
            'total_votes' => $totalVotes,
            'has_voted' => $userVote !== null,
            'user_vote' => $userVote,
            'options' => $this->options->map(function ($option) use ($totalVotes) {
                return [
                    'id' => $option->id,
                    'label' => $option->label,
                    'votes_count' => (int) $option->votes_count,
                    'percent' => $totalVotes > 0
                        ? (int) round($option->votes_count * 100 / $totalVotes)
                        : 0,
                ];
            })->values()->all(),
        ];
    }
}
