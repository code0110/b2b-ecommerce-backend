<?php

use Illuminate\Support\Facades\Route;

// aici poți avea alte rute web specifice (dacă ai nevoie)
// Route::get('/test', function () { return 'ok'; });

// Catch-all pentru SPA (Vue) – IMPORTANT: îl punem la final și excludem /api
Route::view('/{any}', 'app')
    ->where('any', '^(?!api).*$') // nu prinde /api/...
    ->name('spa');
