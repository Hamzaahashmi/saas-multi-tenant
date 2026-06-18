<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    private const DEFAULTS = [
        'timezone'               => 'UTC',
        'date_format'            => 'Y-m-d',
        'notifications_enabled'  => true,
        'company_description'    => '',
        'company_website'        => '',
        'support_email'          => '',
    ];

    public function __construct(protected ActivityLogService $activityLog) {}

    public function index(): JsonResponse
    {
        $tenant = tenancy()->tenant;
        $stored = $tenant->settings ?? [];
        $settings = array_merge(self::DEFAULTS, $stored);

        return response()->json([
            'settings' => $settings,
            'tenant'   => [
                'id'   => $tenant->id,
                'name' => $tenant->name,
                'plan' => $tenant->plan,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorize('manage-settings');

        $validated = $request->validate([
            'timezone'              => ['sometimes', 'string', Rule::in(\DateTimeZone::listIdentifiers())],
            'date_format'           => 'sometimes|string|max:20',
            'notifications_enabled' => 'sometimes|boolean',
            'company_description'   => 'sometimes|nullable|string|max:500',
            'company_website'       => 'sometimes|nullable|url|max:255',
            'support_email'         => 'sometimes|nullable|email|max:255',
        ]);

        $tenant = tenancy()->tenant;
        $merged = array_merge(self::DEFAULTS, $tenant->settings ?? [], $validated);
        $tenant->settings = $merged;
        $tenant->save();

        $this->activityLog->log('settings.updated', null, array_keys($validated), $request);

        return response()->json([
            'message'  => 'Settings updated successfully.',
            'settings' => $merged,
        ]);
    }
}
