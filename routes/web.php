<?php

use Illuminate\Support\Facades\Route;

// Serve React frontend for all routes (SPA)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');

// Keep API routes separate
// API routes are in routes/api.php
