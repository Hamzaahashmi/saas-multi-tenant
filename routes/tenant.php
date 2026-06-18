<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Billing\BillingController;
use App\Http\Controllers\Tenant\ActivityLogController;
use App\Http\Controllers\Tenant\InvitationController;
use App\Http\Controllers\Tenant\ProfileController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\TeamController;
use Illuminate\Support\Facades\Route;

// Blade pages — served on tenant subdomains, auth handled client-side
Route::get('/login',    fn () => view('tenant.login'));
Route::get('/dashboard',fn () => view('tenant.dashboard'));
Route::get('/team',     fn () => view('tenant.team'));
Route::get('/settings', fn () => view('tenant.settings'));
Route::get('/activity', fn () => view('tenant.activity'));
Route::get('/billing',  fn () => view('tenant.billing'));
Route::get('/invite/{token}', fn ($token) => view('tenant.accept-invite', ['token' => $token]));

// Public API
Route::post('/api/invite/{token}/accept', [InvitationController::class, 'accept']);
Route::post('/api/login', [AuthController::class, 'login']);

Route::middleware('tenant.auth')->group(function () {

    Route::post('/api/logout', [AuthController::class, 'logout']);
    Route::get('/api/me',      [AuthController::class, 'me']);
    Route::put('/api/me',          [ProfileController::class, 'update']);
    Route::put('/api/me/password', [ProfileController::class, 'changePassword']);

    Route::prefix('/api/billing')->group(function () {
        Route::get('/',                    [BillingController::class, 'index']);
        Route::post('/checkout',           [BillingController::class, 'checkout']);
        Route::post('/portal',             [BillingController::class, 'portal']);
        Route::delete('/subscription',     [BillingController::class, 'cancel']);
    });

    Route::middleware('subscription.active')->group(function () {

        Route::get('/api/settings',  [SettingsController::class, 'index']);
        Route::put('/api/settings',  [SettingsController::class, 'update']);
        Route::get('/api/activity',  [ActivityLogController::class, 'index']);

        Route::prefix('/api/team')->group(function () {
            Route::get('/',                              [TeamController::class, 'index']);
            Route::post('/', [TeamController::class, 'store'])->middleware('plan.limits');
            Route::put('/{user}',                        [TeamController::class, 'update']);
            Route::delete('/{user}',                     [TeamController::class, 'destroy']);
            Route::get('/roles',                         [TeamController::class, 'roles']);
            Route::get('/invitations',                   [InvitationController::class, 'index']);
            Route::post('/invite',                       [InvitationController::class, 'store']);
            Route::delete('/invitations/{invitation}',   [InvitationController::class, 'destroy']);
        });

        Route::get('/api/dashboard', function () {
            $tenant = tenancy()->tenant;
            return response()->json([
                'tenant' => [
                    'name'          => $tenant->name,
                    'plan'          => $tenant->plan,
                    'status'        => $tenant->subscription_status,
                    'domain'        => $tenant->domains->first()->domain,
                    'on_trial'      => $tenant->onTrial(),
                    'trial_ends_at' => $tenant->trial_ends_at,
                ],
                'stats' => [
                    'team_members'        => \App\Models\User::count(),
                    'roles'               => \Spatie\Permission\Models\Role::count(),
                    'pending_invitations' => \App\Models\Invitation::whereNull('accepted_at')
                                                ->where('expires_at', '>', now())
                                                ->count(),
                ],
            ]);
        });
    });
});
