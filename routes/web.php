<?php

use App\Http\Controllers\CostItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('servicios', ServiceController::class)
        ->parameters(['servicios' => 'service'])
        ->except(['show']);

    Route::resource('suscripciones', SubscriptionController::class)
        ->parameters(['suscripciones' => 'subscription'])
        ->except(['show']);

    Route::resource('pagos', PaymentController::class)
        ->parameters(['pagos' => 'payment'])
        ->except(['show']);

    Route::resource('costos', CostItemController::class)
        ->parameters(['costos' => 'costItem'])
        ->except(['show']);

    Route::get('/finanzas', [FinanceController::class, 'index'])->name('finanzas.index');

    Route::get('/usuario/password', [UserPasswordController::class, 'edit'])
        ->name('user.password.edit');

    Route::put('/usuario/password', [UserPasswordController::class, 'update'])
        ->name('user.password.update');
});

require __DIR__.'/auth.php';
