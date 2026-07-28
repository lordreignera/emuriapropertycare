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
        $middleware->alias([
            'check.subscription' => \App\Http\Middleware\CheckActiveSubscription::class,
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            \Illuminate\Http\Exceptions\PostTooLargeException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->is('inspections/*/digital-twin/models')) {
                $uploadLimitMb = max(1, (int) floor(config('digital_twin.upload_max_kilobytes', 102400) / 1024));

                return redirect()
                    ->back()
                    ->with('error', "That capture source file is larger than the current {$uploadLimitMb} MB upload limit. Increase WAMP/PHP upload limits or use the large-file import workflow.")
                    ->withInput($request->except(['source_file', 'thumbnail_file']));
            }

            return null;
        });
    })->create();
