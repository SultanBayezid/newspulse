<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __invoke(Request $request)
    {
        return ActivityLogResource::collection(
            ActivityLog::query()
                ->with('user.avatar')
                ->when(
                    $request->filled('action'),
                    fn ($q) => $q->where('action', 'like', $request->query('action') . '%')
                )
                ->when(
                    $request->filled('user'),
                    fn ($q) => $q->where('user_id', (int) $request->query('user'))
                )
                ->latest()
                ->paginate(min(100, (int) $request->query('per_page', 25)))
                ->withQueryString()
        );
    }
}
