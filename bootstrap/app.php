<?php

use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\WorkOrderLockedException;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Reglas de dominio de HU2: se muestran como error de formulario, no 500.
        $exceptions->render(function (InvalidStatusTransitionException|WorkOrderLockedException $e, Request $request) {
            return back()->with('error', $e->getMessage());
        });
    })->create();
