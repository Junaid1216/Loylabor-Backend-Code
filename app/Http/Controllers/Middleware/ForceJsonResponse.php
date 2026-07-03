<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Ensure all /api requests return JSON (Postman/mobile without Accept header).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api') || $request->is('api/*')) {
            $request->headers->set('Accept', 'application/json', true);
        }

        return $next($request);
    }
}
