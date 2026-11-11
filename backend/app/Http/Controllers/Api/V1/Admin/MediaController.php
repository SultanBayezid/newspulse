<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(Request $request)
    {
        return MediaResource::collection(
            Media::query()
                ->with('user')
                ->when(
                    $request->filled('q'),
                    fn ($q) => $q->where(
                        fn ($query) => $query
                            ->where('file_name', 'like', '%' . $request->query('q') . '%')
                            ->orWhere('alt', 'like', '%' . $request->query('q') . '%')
                    )
                )
                ->latest()
                ->paginate(min(60, (int) $request->query('per_page', 24)))
                ->withQueryString()
        );
    }

    public function store(StoreMediaRequest $request): MediaResource
    {
        $this->authorize('create', Media::class);

        $file = $request->file('file');

        $dimensions = @getimagesize($file->getRealPath()) ?: null;

        $path = $file->store('media', 'public');

        $media = Media::create([
            'user_id' => $request->user()->id,
            'file_name' => $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'alt' => $request->input('alt'),
            'caption' => $request->input('caption'),
            'credit' => $request->input('credit'),
        ]);

        $this->logger->log('media.uploaded', 'Uploaded image "' . $media->file_name . '"', $media);

        return new MediaResource($media);
    }

    public function update(UpdateMediaRequest $request, Media $media): MediaResource
    {
        $this->authorize('update', $media);

        $media->update($request->validated());

        return new MediaResource($media);
    }

    public function destroy(Request $request, Media $media): Response
    {
        $this->authorize('delete', $media);

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        $this->logger->log('media.deleted', 'Deleted image "' . $media->file_name . '"');

        return response()->noContent();
    }
}
