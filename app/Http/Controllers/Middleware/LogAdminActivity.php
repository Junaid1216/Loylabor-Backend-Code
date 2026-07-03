<?php

namespace App\Http\Middleware;

use App\Models\AdminActivity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only log if admin is authenticated
        if (auth('admin')->check()) {
            $admin = auth('admin')->user();
            
            // Don't log GET requests for views, only log actions
            if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE') || $request->isMethod('PATCH')) {
                $action = $this->getActionFromRequest($request);
                $description = $this->getDescriptionFromRequest($request);
                
                AdminActivity::log($action, null, $description, [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'parameters' => $request->except(['password', '_token', '_method']),
                ]);
            }
        }
        
        return $response;
    }

    /**
     * Get action name from request
     */
    private function getActionFromRequest(Request $request): string
    {
        $route = $request->route();
        if (!$route) {
            return 'unknown';
        }

        $routeName = $route->getName();
        $method = $request->method();

        // Map route names to actions
        $actionMap = [
            'admin.store' => 'created',
            'admin.update' => 'updated',
            'admin.destroy' => 'deleted',
            'staff.store' => 'created',
            'staff.update' => 'updated',
            'staff.destroy' => 'deleted',
            'roles.store' => 'created',
            'roles.update' => 'updated',
            'roles.destroy' => 'deleted',
            'country.store' => 'created',
            'country.update' => 'updated',
            'country.destroy' => 'deleted',
            'state.store' => 'created',
            'state.update' => 'updated',
            'state.destroy' => 'deleted',
            'city.store' => 'created',
            'city.update' => 'updated',
            'city.destroy' => 'deleted',
        ];

        if (isset($actionMap[$routeName])) {
            return $actionMap[$routeName];
        }

        // Default action based on method
        switch ($method) {
            case 'POST':
                return 'created';
            case 'PUT':
            case 'PATCH':
                return 'updated';
            case 'DELETE':
                return 'deleted';
            default:
                return 'unknown';
        }
    }

    /**
     * Get description from request
     */
    private function getDescriptionFromRequest(Request $request): string
    {
        $route = $request->route();
        if (!$route) {
            return 'Performed an action';
        }

        $routeName = $route->getName();
        $parameters = $request->route()->parameters();

        // Map route names to descriptions
        $descriptionMap = [
            'admin.store' => 'Created a new admin',
            'admin.update' => 'Updated admin: ' . ($parameters['admin'] ?? 'unknown'),
            'admin.destroy' => 'Deleted admin: ' . ($parameters['admin'] ?? 'unknown'),
            'staff.store' => 'Created a new staff member',
            'staff.update' => 'Updated staff: ' . ($parameters['staff'] ?? 'unknown'),
            'staff.destroy' => 'Deleted staff: ' . ($parameters['staff'] ?? 'unknown'),
            'roles.store' => 'Created a new role',
            'roles.update' => 'Updated role: ' . ($parameters['role'] ?? 'unknown'),
            'roles.destroy' => 'Deleted role: ' . ($parameters['role'] ?? 'unknown'),
            'country.store' => 'Created a new country',
            'country.update' => 'Updated country: ' . ($parameters['country'] ?? 'unknown'),
            'country.destroy' => 'Deleted country: ' . ($parameters['country'] ?? 'unknown'),
            'state.store' => 'Created a new state',
            'state.update' => 'Updated state: ' . ($parameters['state'] ?? 'unknown'),
            'state.destroy' => 'Deleted state: ' . ($parameters['state'] ?? 'unknown'),
            'city.store' => 'Created a new city',
            'city.update' => 'Updated city: ' . ($parameters['city'] ?? 'unknown'),
            'city.destroy' => 'Deleted city: ' . ($parameters['city'] ?? 'unknown'),
        ];

        return $descriptionMap[$routeName] ?? 'Performed an action';
    }
}
