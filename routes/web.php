<?php

use App\Http\Controllers\CostItemController;
use App\Http\Controllers\CostAllocationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceCatalogController;
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

    Route::get('/catalogos/servicios', [ServiceCatalogController::class, 'index'])
        ->name('catalogos.servicios.index');

    Route::post('/catalogos/servicios', [ServiceCatalogController::class, 'store'])
        ->name('catalogos.servicios.store');

    Route::post('/catalogos/servicios/reordenar', [ServiceCatalogController::class, 'reorder'])
        ->name('catalogos.servicios.reorder');

    Route::put('/catalogos/servicios/{catalogoServicio}', [ServiceCatalogController::class, 'update'])
        ->name('catalogos.servicios.update');

    Route::delete('/catalogos/servicios/{catalogoServicio}', [ServiceCatalogController::class, 'destroy'])
        ->name('catalogos.servicios.destroy');

    Route::resource('suscripciones', SubscriptionController::class)
        ->parameters(['suscripciones' => 'subscription'])
        ->except(['show']);

    Route::post('/suscripciones/{subscription}/duplicar', [SubscriptionController::class, 'duplicate'])
        ->name('suscripciones.duplicate');

    Route::resource('pagos', PaymentController::class)
        ->parameters(['pagos' => 'payment'])
        ->except(['show']);

    Route::resource('costos', CostItemController::class)
        ->parameters(['costos' => 'costItem'])
        ->except(['show']);

    Route::get('/costos/{costItem}/asignaciones', [CostAllocationController::class, 'edit'])
        ->name('costos.asignaciones.edit');

    Route::put('/costos/{costItem}/asignaciones', [CostAllocationController::class, 'update'])
        ->name('costos.asignaciones.update');

    Route::get('/finanzas', [FinanceController::class, 'index'])->name('finanzas.index');
    Route::post('/finanzas/snapshots/generar', [FinanceController::class, 'generateSnapshot'])->name('finanzas.snapshots.generate');

    Route::get('/usuario/password', [UserPasswordController::class, 'edit'])
        ->name('user.password.edit');

    Route::put('/usuario/password', [UserPasswordController::class, 'update'])
        ->name('user.password.update');
});

require __DIR__.'/auth.php';
