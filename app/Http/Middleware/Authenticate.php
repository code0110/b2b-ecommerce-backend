<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Where to redirect unauthenticated users.
     *
     * Pentru API (ruta /api/*) NU redirectăm, lăsăm middleware-ul
     * să returneze 401 JSON.
     * Pentru restul (web) trimitem către /login, care e route-ul SPA.
     */
    protected function redirectTo($request): ?string
    {
        // API → fără redirect, va returna 401
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // SPA frontend are ruta /login în Vue Router, servită de Laravel prin view('app')
        return '/login';
    }
}
