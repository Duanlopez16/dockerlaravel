<?php

use Illuminate\Support\Facades\Route;

Route::controller(App\Http\Controllers\Api\v2\LogController::class)->group(function () {
    Route::get('/logs', 'getLogs')->middleware(['admin']);
});
