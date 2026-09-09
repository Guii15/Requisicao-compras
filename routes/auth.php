<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'escolha'])
        ->name('login');

    Route::get('login/{perfil}', [AuthenticatedSessionController::class, 'create'])
        ->whereIn('perfil', ['vendedor', 'conferencia', 'entrada', 'admin'])
        ->name('login.perfil');

    // O LoginRequest já limita por e-mail+IP, mas essa chave inclui o e-mail:
    // tentar UMA senha em MIL e-mails diferentes nunca estoura o limite de
    // nenhum deles. Este throttle é por IP apenas e fecha essa brecha
    // (password spraying), servindo como segunda camada independente.
    Route::post('login/{perfil}', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->whereIn('perfil', ['vendedor', 'conferencia', 'entrada', 'admin'])
        ->name('login.perfil.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    // Sem limite, este endpoint dispara e-mail a cada requisição: serve tanto
    // para inundar a caixa de uma pessoa quanto para queimar a cota de envio.
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])
        ->middleware('throttle:6,1');

    Route::put('password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
