<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PollResource;
use App\Models\Poll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PollController extends Controller
{
    /**
     * The currently active poll (null when none is running).
     */
    public function active(): JsonResponse|JsonResource
    {
        $poll = Poll::query()
            ->active()
            ->with(['options', 'votes'])
            ->latest()
            ->first();

        if ($poll === null) {
            return response()->json(['data' => null]);
        }

        return new PollResource($poll);
    }

    /**
     * Cast a vote in the active poll (one vote per user).
     */
    public function vote(Request $request, Poll $poll): JsonResponse
    {
        abort_unless($poll->is_active && ($poll->ends_at === null || $poll->ends_at->isFuture()), 403, 'This poll is closed.');

        abort_if($poll->hasVoted($request->user()), 422, 'You have already voted in this poll.');

        $data = $request->validate([
            'poll_option_id' => [
                'required', 'integer',
                Rule::exists('poll_options', 'id')->where('poll_id', $poll->id),
            ],
        ]);

        DB::transaction(function () use ($request, $poll, $data) {
            $poll->votes()->create([
                'user_id' => $request->user()->id,
                'poll_option_id' => $data['poll_option_id'],
            ]);

            $poll->options()->whereKey($data['poll_option_id'])->increment('votes_count');
        });

        $poll->refresh()->load(['options', 'votes']);

        return response()->json([
            'data' => new PollResource($poll),
            'message' => 'Your vote has been recorded.',
        ]);
    }
}
