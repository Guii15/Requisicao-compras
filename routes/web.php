<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\AdminMiddleware;
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

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::patch('/requisicoes/{purchaseRequest}', [AdminController::class, 'update'])->name('requests.update');
});

require __DIR__.'/auth.php';