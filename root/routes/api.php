<?php

use App\Http\Controllers\Api\Admin\CommissionController;
use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->group(function () {
        Route::prefix('commissions')
            ->group(function () {
                Route::get('/', [CommissionController::class, 'index']);
                Route::get('/{id}', [CommissionController::class, 'show']);
                Route::post('/', [CommissionController::class, 'store']);
                Route::patch('/{id}', [CommissionController::class, 'update']);
                Route::delete('/{id}', [CommissionController::class, 'destroy']);
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