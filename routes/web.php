<?php

use Illuminate\Support\Facades\Route;

// Debug route (remove in production)
Route::get('/debug', function () {
    return view('debug');
});

// Serve React frontend for all routes (SPA)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api|debug).*$');

// Keep API routes separate
// API routes are in routes/api.php
