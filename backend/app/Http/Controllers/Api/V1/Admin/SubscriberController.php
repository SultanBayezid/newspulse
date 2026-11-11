<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriberResource;
use App\Models\NewsletterSubscriber;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(Request $request)
    {
        return SubscriberResource::collection(
            NewsletterSubscriber::query()
                ->when(
                    $request->filled('status'),
                    fn ($q) => $q->where('status', $request->query('status'))
                )
                ->when(
                    $request->filled('q'),
                    fn ($q) => $q->where('email', 'like', '%' . $request->query('q') . '%')
                )
                ->latest()
                ->paginate(min(100, (int) $request->query('per_page', 25)))
                ->withQueryString()
        );
    }

    /**
     * Download the subscriber list as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $filename = 'newspulse-subscribers-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'email', 'name', 'status', 'subscribed_at', 'created_at']);

            NewsletterSubscriber::query()
                ->where('status', 'subscribed')
                ->orderBy('email')
                ->chunk(500, function ($subscribers) use ($out) {
                    foreach ($subscribers as $subscriber) {
                        fputcsv($out, [
                            $subscriber->id,
                            $subscriber->email,
                            $subscriber->name,
                            $subscriber->status,
                            $subscriber->subscribed_at?->toDateTimeString(),
                            $subscriber->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function destroy(Request $request, NewsletterSubscriber $subscriber): Response
    {
        $email = $subscriber->email;
        $subscriber->delete();

        $this->logger->log('subscriber.deleted', 'Removed subscriber ' . $email);

        return response()->noContent();
    }
}
