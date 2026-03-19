<?php

use App\Http\Middleware\TenantIdentificationMiddleware;
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
        // Tenant identification middleware alias
        $middleware->alias([
            'tenant.identify' => TenantIdentificationMiddleware::class,
        ]);

        // API grubunu yeniden tanımla - varsayılan throttle:api KALDIRILDI
        // Kendi tenant-aware throttle sistemimizi route seviyesinde kullanıyoruz
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // TenantIdentificationMiddleware, ThrottleRequests'ten ÖNCE çalışmalı
        // Bu sayede rate limiter tenant context'i bulabilir
        $middleware->priority([
            TenantIdentificationMiddleware::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
