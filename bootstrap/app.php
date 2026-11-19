<?php

use App\Http\Middleware\TrackOnlineUsers;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            TrackOnlineUsers::class,
        ]);
    })
    
->withExceptions(function (Exceptions $exceptions) {
$exceptions->render(function (NotFoundHttpException $e, Request $request) {
    if ($request->wantsJson()) {
        return redirect()->intended('spa.fallback');
    }
    return redirect()->intended('spa.fallback');
});

    // ... остальные настройки (level, throttle и т.д.)
})->create();