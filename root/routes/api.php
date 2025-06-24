<?php

use App\Http\Controllers\Api\Admin\CommissionController;
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