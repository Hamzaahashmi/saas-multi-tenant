<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Billing\BillingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Domain Routes
| These run on the main domain (e.g. localhost)
|--------------------------------------------------------------------------
*/

// Public tenant registration
Route::post('/api/register', [AuthController::class, 'register']);

// Stripe webhook — Stripe signature verified inside controller
Route::post('/webhook/stripe', [BillingController::class, 'webhook']);

// Health check
Route::get('/health', fn() => response()->json(['status' => 'ok', 'timestamp' => now()]));

// Super Admin API — protected by API key (Bearer token)
Route::middleware('super.admin')->prefix('/api/admin')->group(function () {
    Route::get('/stats',                        [AdminController::class, 'stats']);
    Route::get('/tenants',                      [AdminController::class, 'index']);
    Route::get('/tenants/{id}',                 [AdminController::class, 'show']);
    Route::post('/tenants/{id}/suspend',        [AdminController::class, 'suspend']);
    Route::post('/tenants/{id}/unsuspend',      [AdminController::class, 'unsuspend']);
});
