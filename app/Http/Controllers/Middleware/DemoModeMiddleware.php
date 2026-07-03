<?php

namespace App\Http\Middleware;

use App\Exceptions\DemoModeEnabledException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoModeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (strtoupper(config('app.app_mode')) !== 'LIVE') {
            // Define an array of routes that are allowed in non-LIVE mode
            $allowedRoutes = [
                'login',
                'register',
                'user-register',
                'user-login',
                'user-verification',
                'logout',
                'admin.login',
                'admin.store-login',
                'admin.logout',
                'website.cart',
                'website.cart.store',
                'website.cart.remove',
                'website.cart.update',
                'website.checkout',
            ];
            if ($request->routeIs(...$allowedRoutes) || $request->method() == 'GET') {
                return $next($request);
            }

            // Check if the request is an AJAX request
            if ($request->ajax() || $request->isXmlHttpRequest()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('This Is Demo Version. You Can Not Change Anything'),
                ], 403); // 403 Forbidden status code
            }

            // For non-AJAX requests, throw DemoModeEnabledException
            throw new DemoModeEnabledException;
        }

        return $next($request);
    }
}
