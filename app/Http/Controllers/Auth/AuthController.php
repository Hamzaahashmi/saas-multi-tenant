<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterTenantRequest;
use App\Services\ActivityLogService;
use App\Services\TenantRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected TenantRegistrationService $registrationService,
        protected ActivityLogService $activityLog,
    ) {}

    public function register(RegisterTenantRequest $request): JsonResponse
    {
        try {
            $result = $this->registrationService->register($request->validated());

            return response()->json([
                'message' => 'Tenant registered successfully. Your 14-day trial has started.',
                'tenant'  => [
                    'id'     => $result['tenant']->id,
                    'name'   => $result['tenant']->name,
                    'domain' => $result['tenant']->domains->first()->domain,
                    'plan'   => $result['tenant']->plan,
                    'trial_ends_at' => $result['tenant']->trial_ends_at,
                ],
                'user'    => [
                    'id'    => $result['user']->id,
                    'name'  => $result['user']->name,
                    'email' => $result['user']->email,
                    'roles' => $result['user']->getRoleNames(),
                ],
                'token'   => $result['token'],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->activityLog->log('auth.login', $user, [], $request);

        return response()->json([
            'message' => 'Login successful.',
            'user'    => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'is_tenant_admin'=> $user->is_tenant_admin,
                'roles'          => $user->getRoleNames(),
                'permissions'    => $user->getAllPermissions()->pluck('name'),
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'is_tenant_admin'=> $user->is_tenant_admin,
                'roles'          => $user->getRoleNames(),
                'permissions'    => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }
}
