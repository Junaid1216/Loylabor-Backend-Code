<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated as staff
        if (!Auth::guard('staff')->check()) {
            return redirect()->route('staff.login')
                ->with('error', 'Please login to access staff panel.');
        }

        // Optional: Check if staff account is active
        $staff = Auth::guard('staff')->user();
        if ($staff && $staff->status != 1) {
            Auth::guard('staff')->logout();
            return redirect()->route('staff.login')
                ->with('error', 'Your account is inactive. Please contact admin.');
        }

        return $next($request);
    }
}
