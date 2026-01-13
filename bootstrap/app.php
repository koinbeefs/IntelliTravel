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
    ->withMiddleware(function (Middleware $middleware) {
        // Handle CORS directly here
        $middleware->validateCsrfTokens(except: [
            // 'stripe/*', // Add exceptions here if needed
        ]);
        
        // This is the Laravel 11 way to allow any origin (easiest for dev/railway)
        $middleware->trustProxies(at: '*');
        
        // You can explicitly configure CORS headers if the default isn't working:
        // (But usually Laravel 11 defaults are 'allow all' for API routes)
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
