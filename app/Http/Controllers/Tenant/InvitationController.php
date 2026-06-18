<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    public function __construct(protected ActivityLogService $activityLog) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage-team');

        $validated = $request->validate([
            'email' => 'required|email',
            'name'  => 'nullable|string|max:255',
            'role'  => ['required', Rule::in(['admin', 'manager', 'member'])],
        ]);

        if (User::where('email', $validated['email'])->exists()) {
            return response()->json(['message' => 'This email is already a team member.'], 422);
        }

        $invitation = Invitation::generate(
            $validated['email'],
            $validated['role'],
            $request->user()->id,
            $validated['name'] ?? null,
        );

        $this->activityLog->log('team.invite.sent', null, [
            'email' => $validated['email'],
            'role'  => $validated['role'],
        ], $request);

        // In production: dispatch a SendInvitationEmail job here
        // Mail::to($validated['email'])->send(new TeamInvitation($invitation));

        return response()->json([
            'message'    => 'Invitation sent successfully.',
            'invitation' => [
                'id'         => $invitation->id,
                'email'      => $invitation->email,
                'role'       => $invitation->role,
                'expires_at' => $invitation->expires_at,
                'token'      => $invitation->token, // Return token so it can be shared or tested
            ],
        ], 201);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation) {
            return response()->json(['message' => 'Invalid invitation token.'], 404);
        }

        if (!$invitation->isPending()) {
            $reason = $invitation->isExpired() ? 'expired' : 'already accepted';
            return response()->json(['message' => "This invitation has {$reason}."], 422);
        }

        if (User::where('email', $invitation->email)->exists()) {
            return response()->json(['message' => 'An account with this email already exists.'], 422);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $invitation->email,
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($invitation->role);

        $invitation->update(['accepted_at' => now()]);

        $this->activityLog->log('team.invite.accepted', $user, [
            'role' => $invitation->role,
        ], $request);

        return response()->json([
            'message' => 'Invitation accepted. Your account has been created.',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
            'token' => $user->createToken('auth_token')->plainTextToken,
        ], 201);
    }

    public function index(): JsonResponse
    {
        $this->authorize('manage-team');

        $invitations = Invitation::whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get(['id', 'email', 'name', 'role', 'expires_at', 'created_at']);

        return response()->json(['invitations' => $invitations]);
    }

    public function destroy(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorize('manage-team');

        if ($invitation->accepted_at) {
            return response()->json(['message' => 'Cannot revoke an accepted invitation.'], 422);
        }

        $this->activityLog->log('team.invite.revoked', null, ['email' => $invitation->email], $request);

        $invitation->delete();

        return response()->json(['message' => 'Invitation revoked.']);
    }
}
