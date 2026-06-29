<?php

use App\Http\Middleware\SecureHeaders;
use App\Http\Middleware\VerifyApiKey;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [
            SecureHeaders::class,
        ]);

        $middleware->alias([
            'api.key' => VerifyApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Consistent JSON error shapes for the API.
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $_e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (ThrottleRequestsException $_e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'Too many requests. Please slow down.'], 429);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (NotFoundHttpException $_e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'Resource not found.'], 404);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                $message = $e->getMessage() ?: 'Request failed.';

                return response()->json(['error' => $message], $e->getStatusCode());
            }
        });
    })->create();
