<?php

namespace App\Http\Middleware;

use App\Exceptions\AccessPermissionDeniedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsVendorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('web')->check() && optional(auth()->user())->seller) {
            return $next($request);
        }

        throw new AccessPermissionDeniedException;
    }
}
