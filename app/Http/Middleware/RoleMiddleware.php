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
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // if no roles are specified, default to allowing 'user' role
        $allowedRoles = empty($roles) ? ['user'] : $roles;

        if (!$request->user() || !in_array($request->user()->role, $allowedRoles)) {
            return response()->json(['message' => 'Credentials not valid'], 403);
        }

        return $next($request);
    }
}
