<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function log(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?Request $request = null
    ): ActivityLog {
        $user = Auth::user();

        return ActivityLog::create([
            'user_id'       => $user?->id,
            'user_name'     => $user?->name,
            'action'        => $action,
            'subject_type'  => $subject ? get_class($subject) : null,
            'subject_id'    => $subject?->getKey(),
            'subject_label' => $subject ? ($subject->name ?? $subject->email ?? (string) $subject->getKey()) : null,
            'properties'    => $properties ?: null,
            'ip_address'    => $request?->ip(),
        ]);
    }
}
