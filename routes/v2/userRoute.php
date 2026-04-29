<?php

use Illuminate\Support\Facades\Route;

Route::controller(App\Http\Controllers\Api\v2\UserController::class)->group(function () {
    Route::get('/user', 'getUserToken');
});
