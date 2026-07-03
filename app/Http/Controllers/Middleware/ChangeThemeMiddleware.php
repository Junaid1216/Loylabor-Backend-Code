<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChangeThemeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->initTheme();
        return $next($request);
    }

    protected function initTheme(): void
    {
        $setting = cache('setting');
        $requestedTheme  = request('theme');
        $availableThemes = themeList();
        $defaultTheme    = $setting->theme ?? 1;

        if ($requestedTheme) {
            // Validate against available themes
            $selectedTheme = in_array($requestedTheme, $availableThemes)
                ? $requestedTheme
                : $defaultTheme;

            // Store in session (per user)
            session(['selected_theme' => $selectedTheme]);
        } else {
            // Pull from session if available, otherwise default
            $selectedTheme = session('selected_theme', $defaultTheme);

            // Validate session theme
            if (!in_array($selectedTheme, $availableThemes)) {
                $selectedTheme = $defaultTheme;
                session(['selected_theme' => $defaultTheme]);
            }
        }

        config(['services.theme' => $selectedTheme]);
    }
}
