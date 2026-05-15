<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ValidateKeycloakToken;
use App\Http\Middleware\admin;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(function () {

        // WEB PRINCIPAL
        // Route::middleware('web')
        //     ->group(base_path('routes/web.php'));

        // // ADMIN
        // Route::middleware('web')
        //     ->prefix('admin')
        //     ->name('admin.')
        //     ->group(base_path('routes/admin.php'));

        // API V2
        Route::middleware(['api', 'keycloak.auth'])
            ->prefix('api/v2')
            ->name('api.v2.')
            ->group(
                function () {
                    require base_path('routes/v2/userRoute.php');
                    require base_path('routes/v2/probeRoute.php');
                    require base_path('routes/v2/logRoute.php');
                }
            );
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'keycloak.auth' => ValidateKeycloakToken::class,
            'admin' => admin::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
