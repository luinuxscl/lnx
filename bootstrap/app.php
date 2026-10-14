<?php

use App\Http\Middleware\ApplySiteSettings;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsureAccountAccess;
use App\Http\Middleware\EnsureOpenRegistration;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            ApplySiteSettings::class,
            SetLocale::class,
            EnsureAccountAccess::class,
            EnsureOpenRegistration::class,
            CheckMaintenanceMode::class,
        ]);

        $middleware->appendToPriorityList(StartSession::class, ApplySiteSettings::class);
        $middleware->appendToPriorityList(ApplySiteSettings::class, SetLocale::class);
        $middleware->appendToPriorityList(SetLocale::class, EnsureAccountAccess::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
