<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckStaffStatus
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
        if (Auth::guard('staff')->check()) {
            $staff = Auth::guard('staff')->user();
            
            // Check if staff is inactive (handle both 'active' and '1' as active)
            if ($staff->status !== 'active' && $staff->status !== '1') {
                // Logout the staff
                Auth::guard('staff')->logout();
                
                // Clear session
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirect with message for toast
                return redirect()->route('staff.login')
                    ->with('message', 'Your account is deactivated by Admin. Please contact with admin')
                    ->with('alert-type', 'error');
            }
        }
        
        return $next($request);
    }
}
