<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Record an editorial action.
     */
    public function log(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
    ): ActivityLog {
        $user = auth()->user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
