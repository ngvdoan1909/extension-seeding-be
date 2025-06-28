<?php

use App\Http\Controllers\Web\Admin\WebsiteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Admin\CommissionController;

Route::get('/', function () {
    return view('admin.layout.master');
});

Route::prefix('admin')
    ->name('admin.')
    // ->middleware('auth:api')
    ->group(function () {
        Route::prefix('websites')
            ->name('websites.')
            ->group(function () {
                Route::get('/', [WebsiteController::class, 'index'])->name('index');
                Route::get('/show/{id}', [WebsiteController::class, 'show'])->name('show');
                Route::get('/create', [WebsiteController::class, 'create'])->name('create');
                Route::post('/create', [WebsiteController::class, 'store'])->name('store');
                Route::patch('/update/{id}', [WebsiteController::class, 'update'])->name('update');
                Route::delete('/delete/{id}', [WebsiteController::class, 'destroy'])->name('destroy');
            });

        // Route::prefix('commissions')
        //     ->name('commissions.')
        //     ->group(function () {
        //         Route::get('/', [CommissionController::class, 'index'])->name('index');
        //         Route::get('/{id}', [CommissionController::class, 'show'])->name('show');
        //         Route::get('/create', [CommissionController::class, 'create'])->name('create');
        //         Route::post('/create', [CommissionController::class, 'store'])->name('store');
        //         Route::patch('/update/{id}', [CommissionController::class, 'update'])->name('update');
        //         Route::delete('/delete/{id}', [CommissionController::class, 'destroy'])->name('destroy');
        //     });
    });