<?php

use Illuminate\Support\Facades\Route;

// Sanctum CSRF Cookie route
Route::get('/sanctum/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show']);