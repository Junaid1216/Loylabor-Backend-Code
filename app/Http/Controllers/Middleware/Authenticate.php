<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they is not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Get the current path
        $path = $request->path();
        
        // Check if the request is for staff routes - STAFF PRIORITY
        if (strpos($path, 'staff/') === 0 || $request->is('staff/*')) {
            return route('staff.login');
        }
        
        // Check if the request is for admin routes
        if (strpos($path, 'admin/') === 0 || $request->is('admin/*')) {
            return route('admin.login');
        }
        
        // Check route names as backup
        if ($request->route()) {
            $routeName = $request->route()->getName();
            if ($routeName && strpos($routeName, 'staff.') === 0) {
                return route('staff.login');
            }
            
            if ($routeName && strpos($routeName, 'admin.') === 0) {
                return route('admin.login');
            }
        }
        
        // Default to regular login
        return $request->expectsJson() ? null : route('login');
    }
}
