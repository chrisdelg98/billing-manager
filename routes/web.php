<?php

use App\Http\Controllers\CostItemController;
use App\Http\Controllers\CostAllocationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTestController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentVoucherController;
use App\Http\Controllers\MigrationToolController;
use App\Http\Controllers\ServiceCatalogController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SubscriptionLicenseController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::view('/documentacion/api', 'documentacion.api')
        ->name('documentacion.api');

    Route::get('/herramientas/migraciones', [MigrationToolController::class, 'index'])
        ->name('herramientas.migraciones.index');

    Route::post('/herramientas/migraciones/ejecutar', [MigrationToolController::class, 'run'])
        ->name('herramientas.migraciones.run');

    Route::post('/herramientas/migraciones/baseline', [MigrationToolController::class, 'baseline'])
        ->name('herramientas.migraciones.baseline');

    Route::get('/herramientas/correos-prueba', [EmailTestController::class, 'index'])
        ->name('herramientas.correos-prueba.index');

    Route::post('/herramientas/correos-prueba', [EmailTestController::class, 'send'])
        ->name('herramientas.correos-prueba.send');

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

    Route::post('/suscripciones/{subscription}/licencia/rotar', [SubscriptionLicenseController::class, 'rotate'])
        ->name('suscripciones.licencia.rotate');

    Route::post('/suscripciones/{subscription}/licencia/revelar', [SubscriptionLicenseController::class, 'reveal'])
        ->name('suscripciones.licencia.reveal');

    Route::post('/suscripciones/{subscription}/licencia/revocar', [SubscriptionLicenseController::class, 'revoke'])
        ->name('suscripciones.licencia.revoke');

    Route::post('/suscripciones/{subscription}/licencia/reactivar', [SubscriptionLicenseController::class, 'reactivate'])
        ->name('suscripciones.licencia.reactivate');

    Route::resource('pagos', PaymentController::class)
        ->parameters(['pagos' => 'payment'])
        ->except(['show']);

    Route::get('/comprobantes/pagos/{payment}', [PaymentVoucherController::class, 'payment'])
        ->name('comprobantes.pagos.show');

    Route::post('/comprobantes/pagos/{payment}/enviar', [PaymentVoucherController::class, 'sendPaymentEmail'])
        ->name('comprobantes.pagos.send');

    Route::get('/comprobantes/suscripciones/{subscription}/recordatorio', [PaymentVoucherController::class, 'reminder'])
        ->name('comprobantes.suscripciones.recordatorio');

    Route::post('/comprobantes/suscripciones/{subscription}/recordatorio/enviar', [PaymentVoucherController::class, 'sendReminderEmail'])
        ->name('comprobantes.suscripciones.recordatorio.send');

    Route::get('/comprobantes/suscripciones/{subscription}/bienvenida', [PaymentVoucherController::class, 'welcome'])
        ->name('comprobantes.suscripciones.bienvenida');

    Route::post('/comprobantes/suscripciones/{subscription}/bienvenida/enviar', [PaymentVoucherController::class, 'sendWelcomeEmail'])
        ->name('comprobantes.suscripciones.bienvenida.send');

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
