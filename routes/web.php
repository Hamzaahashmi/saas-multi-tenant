<?php

use Illuminate\Support\Facades\Route;

// Central domain — marketing and registration pages
Route::get('/', fn () => view('central.landing'));
Route::get('/register', fn () => view('central.register'));
