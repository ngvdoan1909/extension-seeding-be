<?php

use App\Http\Controllers\Web\Admin\UserController;
use App\Http\Controllers\Web\Admin\WebsiteController;
use App\Http\Controllers\Web\AdminAuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Admin\CommissionController;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth:web', 'admin.role'])
    ->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [AdminAuthController::class, 'dashboard'])->name('dashboard');

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

        Route::prefix('commissions')
            ->name('commissions.')
            ->group(function () {
                Route::get('/', [CommissionController::class, 'index'])->name('index');
                Route::get('/show/{id}', [CommissionController::class, 'show'])->name('show');
                Route::get('/create', [CommissionController::class, 'create'])->name('create');
                Route::post('/create', [CommissionController::class, 'store'])->name('store');
                Route::patch('/update/{id}', [CommissionController::class, 'update'])->name('update');
                Route::delete('/delete/{id}', [CommissionController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('users')
            ->name('users.')
            ->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::get('/show/{id}', [UserController::class, 'show'])->name('show');
                Route::get('/create', [UserController::class, 'create'])->name('create');
                Route::post('/store', [UserController::class, 'store'])->name('store');
                Route::patch('/update/{id}', [UserController::class, 'update'])->name('update');
                Route::delete('/delete/{id}', [UserController::class, 'destroy'])->name('destroy');
            });
    });

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('post.login');
});