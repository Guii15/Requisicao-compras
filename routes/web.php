<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConferenciaController;
use App\Http\Controllers\PendenciaController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ConferenteMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('requests.index');
});

Route::get('/dashboard', function () {
    return redirect()->route('requests.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/requisicoes', [PurchaseRequestController::class, 'index'])->name('requests.index');
    Route::get('/requisicoes/nova', [PurchaseRequestController::class, 'create'])->name('requests.create');
    Route::post('/requisicoes', [PurchaseRequestController::class, 'store'])->name('requests.store');
    Route::get('/requisicoes/{purchaseRequest}/editar', [PurchaseRequestController::class, 'edit'])->name('requests.edit');
    Route::patch('/requisicoes/{purchaseRequest}', [PurchaseRequestController::class, 'update'])->name('requests.update');
    Route::get('/requisicoes/{purchaseRequest}/exportar', [PurchaseRequestController::class, 'export'])->name('requests.export');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::patch('/requisicoes/{purchaseRequest}', [AdminController::class, 'update'])->name('requests.update');
    Route::get('/requisicoes/{purchaseRequest}/exportar', [AdminController::class, 'export'])->name('requests.export');

    Route::get('/mensal/{year}/{month}', [AdminController::class, 'monthlyRequests'])->name('monthly');
    Route::get('/usuarios', [AdminController::class, 'users'])->name('users.index');
    Route::post('/usuarios', [AdminController::class, 'storeUser'])->name('users.store');
    Route::delete('/usuarios/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    Route::patch('/usuarios/{user}/senha', [AdminController::class, 'resetPassword'])->name('users.resetPassword');
});

Route::middleware(['auth', ConferenteMiddleware::class])->prefix('conferencia')->name('conferencia.')->group(function () {
    Route::get('/', [ConferenciaController::class, 'index'])->name('index');
    Route::patch('/{purchaseRequest}', [ConferenciaController::class, 'conferir'])->name('conferir');
});

Route::middleware(['auth', AdminMiddleware::class])->prefix('pendencias')->name('pendencias.')->group(function () {
    Route::get('/', [PendenciaController::class, 'index'])->name('index');
    Route::patch('/{purchaseRequest}', [PendenciaController::class, 'resolver'])->name('resolver');
});

require __DIR__.'/auth.php';