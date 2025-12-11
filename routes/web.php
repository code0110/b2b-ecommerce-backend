<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/login', function () {
    // Dacă vrei doar să nu mai dea eroare și folosești SPA separat:
    return response()->json([
        'message' => 'Te rugăm să te autentifici prin interfața SPA / front-end.',
    ], 401);
})->name('login');