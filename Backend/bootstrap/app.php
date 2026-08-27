<?php

use App\Exceptions\EmailNotVerifiedException;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureIdempotency;
use App\Http\Middleware\PreventGuestDemoMutations;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->throttleApi('api');

        $middleware->redirectGuestsTo(fn () => null);

        $middleware->api(prepend: [SetLocale::class]);

        // Applies to both the web (SPA shell) and api response stacks.
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'active' => EnsureAccountIsActive::class,
            'idempotency' => EnsureIdempotency::class,
            'maintenance' => CheckMaintenanceMode::class,
            'prevent-guest-mutations' => PreventGuestDemoMutations::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => response()->json([
                    'success' => false,
                    'message' => __('exceptions.validation_failed'),
                    'data' => null,
                    'errors' => $e->errors(),
                ], $e->status),

                $e instanceof EmailNotVerifiedException => response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => null,
                    'errors' => null,
                ], 403),

                $e instanceof AuthenticationException => response()->json([
                    'success' => false,
                    'message' => __('exceptions.must_login'),
                    'data' => null,
                    'errors' => null,
                ], 401),

                $e instanceof AuthorizationException,
                $e instanceof AccessDeniedHttpException => response()->json([
                    'success' => false,
                    'message' => __('exceptions.unauthorized'),
                    'data' => null,
                    'errors' => null,
                ], 403),

                $e instanceof InvalidSignatureException => response()->json([
                    'success' => false,
                    'message' => __('exceptions.invalid_signature'),
                    'data' => null,
                    'errors' => null,
                ], 403),

                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => response()->json([
                    'success' => false,
                    'message' => __('exceptions.not_found'),
                    'data' => null,
                    'errors' => null,
                ], 404),

                $e instanceof TooManyRequestsHttpException => response()->json([
                    'success' => false,
                    'message' => __('exceptions.too_many_requests'),
                    'data' => null,
                    'errors' => null,
                ], 429),

                $e instanceof HttpExceptionInterface => response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: __('exceptions.generic_request_error'),
                    'data' => null,
                    'errors' => null,
                ], $e->getStatusCode()),

                default => response()->json([
                    'success' => false,
                    'message' => config('app.debug')
                        ? $e->getMessage()
                        : __('exceptions.unexpected_error'),
                    'data' => null,
                    'errors' => config('app.debug') ? [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ] : null,
                ], 500),
            };
        });
    })->create();
