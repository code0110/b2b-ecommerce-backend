<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Utilizare în rute:
     * ->middleware('role:admin,operator,marketer,agent,sales_director')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
      $user = $request->user();

      if (!$user) {
          abort(401, 'Unauthenticated.');
      }

      // roluri permise (din stringul din route, ex: 'admin','operator' etc.)
      $allowed = collect($roles)
          ->map(fn ($r) => strtolower($r))
          ->filter()
          ->values()
          ->all();

      // rolurile user-ului – folosim slug sau code
      $userRoles = ($user->roles ?? collect())
          ->map(function ($role) {
              return strtolower($role->slug ?? $role->code ?? '');
          })
          ->filter()
          ->values()
          ->all();

      $hasRole = count(array_intersect($allowed, $userRoles)) > 0;

      if (!$hasRole) {
          abort(403, 'Forbidden.');
      }

      return $next($request);
    }
}
