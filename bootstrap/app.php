<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // RFC 7807 Problem Details for all API validation / auth errors
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'type'   => 'https://catframework-api.dev/errors/validation',
                    'title'  => 'Validation failed',
                    'status' => 422,
                    'detail' => collect($e->errors())->flatten()->first(),
                    'errors' => $e->errors(),
                ], 422)->header('Content-Type', 'application/problem+json');
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'type'   => 'https://catframework-api.dev/errors/unauthenticated',
                    'title'  => 'Unauthenticated',
                    'status' => 401,
                    'detail' => 'A valid API token is required.',
                ], 401)->header('Content-Type', 'application/problem+json');
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'type'   => 'https://catframework-api.dev/errors/forbidden',
                    'title'  => 'Forbidden',
                    'status' => 403,
                    'detail' => 'You do not have permission to perform this action.',
                ], 403)->header('Content-Type', 'application/problem+json');
            }
        });
    })->create();
