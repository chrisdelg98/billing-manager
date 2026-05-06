<?php

use App\Http\Controllers\UserPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/usuario/password', [UserPasswordController::class, 'edit'])
        ->name('user.password.edit');

    Route::put('/usuario/password', [UserPasswordController::class, 'update'])
        ->name('user.password.update');
});

require __DIR__.'/auth.php';
