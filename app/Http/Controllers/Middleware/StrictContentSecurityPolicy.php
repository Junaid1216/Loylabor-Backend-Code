<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StrictContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $nonce = base64_encode(random_bytes(16)); // Generate secure nonce

        $csp = "
        default-src 'self';
        script-src 'self' 'nonce-$nonce' https://apis.google.com https://cdnjs.cloudflare.com https://www.googletagmanager.com https://www.google-analytics.com;
        style-src 'self' 'nonce-$nonce' https://fonts.googleapis.com;
        img-src 'self' data: https://www.google-analytics.com;
        font-src 'self' https://fonts.gstatic.com;
        connect-src 'self' https://api.example.com https://www.google-analytics.com;
        frame-ancestors 'none';
        object-src 'none';
        base-uri 'self';
        form-action 'self';
    ";

        $response = $next($request);
        $response->headers->set('Content-Security-Policy', preg_replace('/\s+/', ' ', trim($csp))); // Minify CSP header

        return $response;
    }
}
