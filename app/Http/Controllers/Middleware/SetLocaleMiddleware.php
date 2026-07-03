<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (Session::has('lang')) {
                App::setLocale(Session::get('lang'));
            } else {
                $defaultLang = allLanguages()->firstWhere('is_default', true);

                if ($defaultLang) {
                    setLanguage($defaultLang->code);

                    App::setLocale($defaultLang->code);
                } else {
                    App::setLocale(config('app.locale', 'en'));
                }
            }

            if (!session()->has('currency_code') && $currency = allCurrencies()->firstWhere('is_default', 'yes')) {
                session()->put([
                    'currency_code'     => $currency->currency_code,
                    'currency_position' => $currency->currency_position,
                    'currency_icon'     => $currency->currency_icon,
                    'currency_rate'     => $currency->currency_rate,
                ]);
            }

        } catch (Exception $e) {
            Log::error('[Locale Middleware] Error setting locale: ' . $e->getMessage());

            App::setLocale(config('app.locale', 'en'));
        }

        return $next($request);
    }
}
