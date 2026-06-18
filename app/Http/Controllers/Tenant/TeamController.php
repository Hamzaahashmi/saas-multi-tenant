<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class TeamController extends Controller
{
    public function __construct(protected ActivityLogService $activityLog) {}

    public function index(): JsonResponse
    {
        $members = User::with('roles')->orderBy('name')->get()->map(fn($u) => [
            'id'             => $u->id,
            'name'           => $u->name,
            'email'          => $u->email,
            'is_tenant_admin'=> $u->is_tenant_admin,
            'roles'          => $u->getRoleNames(),
            'created_at'     => $u->created_at->toDateString(),
        ]);

        return response()->json(['members' => $members]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage-team');

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'role'     => ['required', Rule::in(['admin', 'manager', 'member'])],
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        $this->activityLog->log('team.member.added', $user, ['role' => $validated['role']], $request);

        return response()->json([
            'message' => 'Team member added successfully.',
            'member'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('manage-team');

        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'manager', 'member'])],
        ]);

        $user->syncRoles([$validated['role']]);

        $this->activityLog->log('team.member.role_updated', $user, ['new_role' => $validated['role']], $request);

        return response()->json([
            'message' => 'Role updated.',
            'member'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('manage-team');

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot remove yourself.'], 422);
        }

        if ($user->is_tenant_admin) {
            return response()->json(['message' => 'Cannot remove the tenant owner.'], 422);
        }

        $this->activityLog->log('team.member.removed', $user, [], $request);

        $user->delete();

        return response()->json(['message' => 'Team member removed.']);
    }

    public function roles(): JsonResponse
    {
        $roles = Role::all()->pluck('name');
        return response()->json(['roles' => $roles]);
    }
}
