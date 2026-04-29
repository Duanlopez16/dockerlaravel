<?php

use Illuminate\Support\Facades\Route;


Route::controller(App\Http\Controllers\Api\v2\ProveController::class)->group(function () {
    Route::get('/livenessprobe', 'livenessProbe')->withoutMiddleware(['keycloak.auth']);
    Route::get('/startup', 'startup')->withoutMiddleware(['keycloak.auth']);
    Route::get('/readinessprobe', 'readiness')->withoutMiddleware(['keycloak.auth']);
});
