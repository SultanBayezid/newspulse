<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PollRequest;
use App\Http\Resources\PollResource;
use App\Models\Poll;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(Request $request)
    {
        return PollResource::collection(
            Poll::query()
                ->with(['options', 'votes'])
                ->latest()
                ->paginate(min(50, (int) $request->query('per_page', 20)))
                ->withQueryString()
        );
    }

    public function store(PollRequest $request): PollResource
    {
        $this->authorize('create', Poll::class);

        $poll = DB::transaction(function () use ($request) {
            $poll = Poll::create([
                'question' => $request->input('question'),
                'description' => $request->input('description'),
                'is_active' => $request->boolean('is_active', true),
                'ends_at' => $request->date('ends_at'),
            ]);

            foreach (array_values($request->input('options')) as $index => $option) {
                $poll->options()->create([
                    'label' => $option['label'],
                    'position' => $index,
                ]);
            }

            return $poll;
        });

        $this->logger->log('poll.created', 'Created poll "' . $poll->question . '"', $poll);

        return new PollResource($poll->load(['options', 'votes']));
    }

    public function update(PollRequest $request, Poll $poll): PollResource
    {
        $this->authorize('update', $poll);

        DB::transaction(function () use ($request, $poll) {
            $poll->update([
                'question' => $request->input('question'),
                'description' => $request->input('description'),
                'is_active' => $request->boolean('is_active', true),
                'ends_at' => $request->date('ends_at'),
            ]);

            $incoming = array_values($request->input('options', []));
            $keepIds = collect($incoming)->pluck('id')->filter()->all();

            // Delete options that were removed in the editor.
            $poll->options()->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))->delete();

            foreach ($incoming as $index => $option) {
                if (! empty($option['id'])) {
                    $poll->options()->whereKey($option['id'])->update([
                        'label' => $option['label'],
                        'position' => $index,
                    ]);
                } else {
                    $poll->options()->create([
                        'label' => $option['label'],
                        'position' => $index,
                    ]);
                }
            }

            // Keep denormalized counters honest after edits.
            $poll->options()->each(function ($option) {
                $option->update([
                    'votes_count' => $option->votes()->count(),
                ]);
            });
        });

        $this->logger->log('poll.updated', 'Updated poll "' . $poll->question . '"', $poll);

        return new PollResource($poll->fresh(['options', 'votes'])->loadCount('votes'));
    }

    public function destroy(Request $request, Poll $poll): Response
    {
        $this->authorize('delete', $poll);

        $question = $poll->question;
        $poll->delete();

        $this->logger->log('poll.deleted', 'Deleted poll "' . $question . '"');

        return response()->noContent();
    }
}
