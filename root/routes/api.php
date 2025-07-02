<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Client\WorkerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('workers')
    ->middleware('auth:api')
    ->group(function () {
        Route::post('start-worker', [WorkerController::class, 'startWorker']);
        Route::delete('cancel-worker/{id}', [WorkerController::class, 'cancelWorker']);
        Route::post('worker-session', [WorkerController::class, 'startWorkerSession']);

        Route::withoutMiddleware('auth:api')
            ->group(function () {
                Route::post('check-phone', [WorkerController::class, 'checkPhone']);
                Route::post('get-code', [WorkerController::class, 'getCode']);
            });
    });

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify', [AuthController::class, 'verifyToken']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});