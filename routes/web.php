<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\SitemapController;

// aici poți avea alte rute web specifice (dacă ai nevoie)
// Route::get('/test', function () { return 'ok'; });

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    $base = rtrim(config('app.url') ?: request()->getSchemeAndHttpHost(), '/');
    $lines = [];
    $lines[] = 'User-agent: *';
    $lines[] = 'Allow: /';
    $lines[] = 'Sitemap: ' . $base . '/sitemap.xml';
    return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
});

// Catch-all pentru SPA (Vue) – IMPORTANT: îl punem la final și excludem /api
Route::view('/{any}', 'app')
    ->where('any', '^(?!api).*$') // nu prinde /api/...
    ->name('spa');
