<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'UnAuthenticated'], 401);
        }
        $guard = Arr::get($exception->guards(), '0');
        switch ($guard) {
            case 'admin':
                $login = '/admin/login';
                break;

            default:
                $login = '/login';
        }

        return Redirect()->guest($login);
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof AccessPermissionDeniedException || $exception instanceof DemoModeEnabledException) {
            return $exception->render($request);
        }

        if ($request->is('api') || $request->is('api/*')) {
            if ($exception instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'HTTP method not allowed for this API endpoint.',
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'allowed' => $exception->getHeaders()['Allow'] ?? null,
                    'hint' => str_contains($request->path(), 'register')
                        ? 'Use POST /api/register with JSON or form-data body.'
                        : (str_contains($request->path(), 'districts')
                            ? 'Use GET /api/districts (POST is not supported).'
                            : null),
                ], 405);
            }

            if ($exception instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'API endpoint not found.',
                    'path' => $request->path(),
                    'method' => $request->method(),
                ], 404);
            }
        }

        return parent::render($request, $exception);
    }
}
