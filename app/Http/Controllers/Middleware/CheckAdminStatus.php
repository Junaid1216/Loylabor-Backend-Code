<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAdminStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            
            // Check if admin is inactive
            if ($admin->status !== 'active') {
                // Logout the admin
                Auth::guard('admin')->logout();
                
                // Clear session
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirect with message for toast
                return redirect()->route('admin.login')
                    ->with('message', 'Your account is deactivated by Admin. Please contact with admin')
                    ->with('alert-type', 'error');
            }
        }
        
        return $next($request);
    }
}
