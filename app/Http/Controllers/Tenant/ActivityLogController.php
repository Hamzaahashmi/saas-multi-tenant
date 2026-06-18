<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('view-activity-log');

        $query = ActivityLog::query()->latest();

        if ($request->filled('action')) {
            $query->where('action', 'like', $request->input('action') . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $logs = $query->paginate($request->integer('per_page', 25));

        return response()->json($logs);
    }
}
