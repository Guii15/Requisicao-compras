<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConferenciaController;
use App\Http\Controllers\PendenciaController;
use App\Http\Controllers\EntradaController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ConferenteMiddleware;
use App\Http\Middleware\ConferenciaVisualizacaoMiddleware;
use App\Http\Middleware\EntradaMiddleware;
use App\Http\Middleware\VendedorMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Limites de requisição (throttle) nas rotas de escrita
|--------------------------------------------------------------------------
| Os números abaixo são por minuto, por usuário autenticado. Foram escolhidos
| MUITO acima do uso real (um vendedor abre ~20 requisições por DIA) porque o
| objetivo aqui não é racionar trabalho — é impedir que um script em laço
| encha o banco. Limite que atrapalha quem está trabalhando é pior do que
| limite nenhum, porque vira motivo para desligarem a proteção.
|
| ESCRITA_NORMAL  30/min  criação e edição pelo próprio usuário
| ESCRITA_FLUXO   60/min  conferência, pendência e entrada — quem opera esses
|                         painéis processa vários itens seguidos
| ESCRITA_CONTA   10/min  mexe em conta de usuário; deve ser raro
*/
const ESCRITA_NORMAL = 'throttle:30,1';
const ESCRITA_FLUXO  = 'throttle:60,1';
const ESCRITA_CONTA  = 'throttle:10,1';

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    return match (true) {
        $user->isAdmin()      => redirect()->route('admin.index'),
        $user->isConferente() => redirect()->route('conferencia.index'),
        $user->isEntrada()    => redirect()->route('entrada.index'),
        default                => redirect()->route('requests.index'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', VendedorMiddleware::class])->group(function () {
    Route::get('/requisicoes', [PurchaseRequestController::class, 'index'])->name('requests.index');
    Route::get('/requisicoes/nova', [PurchaseRequestController::class, 'create'])->name('requests.create');
    Route::post('/requisicoes', [PurchaseRequestController::class, 'store'])->middleware(ESCRITA_NORMAL)->name('requests.store');
    Route::get('/requisicoes/{purchaseRequest}/editar', [PurchaseRequestController::class, 'edit'])->name('requests.edit');
    Route::patch('/requisicoes/{purchaseRequest}', [PurchaseRequestController::class, 'update'])->middleware(ESCRITA_NORMAL)->name('requests.update');
    Route::get('/requisicoes/{purchaseRequest}/exportar', [PurchaseRequestController::class, 'export'])->name('requests.export');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware(ESCRITA_CONTA)->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware(ESCRITA_CONTA)->name('profile.destroy');
});

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::patch('/requisicoes/{purchaseRequest}', [AdminController::class, 'update'])->middleware(ESCRITA_FLUXO)->name('requests.update');
    Route::get('/requisicoes/{purchaseRequest}/exportar', [AdminController::class, 'export'])->name('requests.export');

    Route::get('/mensal/{year}/{month}', [AdminController::class, 'monthlyRequests'])->name('monthly');
    Route::get('/itens-mais-solicitados', [AdminController::class, 'itensMaisSolicitados'])->name('itens-mais-solicitados');
    Route::get('/historico-compras', [AdminController::class, 'historicoCompras'])->name('historico-compras');
    Route::get('/historico-compras/planilha', [AdminController::class, 'downloadPlanilhaOriginal'])->name('historico-compras.planilha.download');
    Route::get('/historico-compras/planilha/{aba}', [AdminController::class, 'downloadAbaPlanilhaOriginal'])->name('historico-compras.planilha.download-aba');

    Route::get('/usuarios', [AdminController::class, 'users'])->name('users.index');
    Route::post('/usuarios', [AdminController::class, 'storeUser'])->middleware(ESCRITA_CONTA)->name('users.store');
    Route::delete('/usuarios/{user}', [AdminController::class, 'destroyUser'])->middleware(ESCRITA_CONTA)->name('users.destroy');
    Route::patch('/usuarios/{user}/senha', [AdminController::class, 'resetPassword'])->middleware(ESCRITA_CONTA)->name('users.resetPassword');
    Route::patch('/usuarios/{user}/perfil', [AdminController::class, 'updateRole'])->middleware(ESCRITA_CONTA)->name('users.updateRole');
});

Route::middleware(['auth'])->prefix('conferencia')->name('conferencia.')->group(function () {
    Route::get('/', [ConferenciaController::class, 'index'])->middleware(ConferenciaVisualizacaoMiddleware::class)->name('index');
    Route::patch('/{purchaseRequest}', [ConferenciaController::class, 'conferir'])->middleware([ConferenteMiddleware::class, ESCRITA_FLUXO])->name('conferir');
});

Route::middleware(['auth', AdminMiddleware::class])->prefix('pendencias')->name('pendencias.')->group(function () {
    Route::get('/', [PendenciaController::class, 'index'])->name('index');
    Route::patch('/{purchaseRequest}', [PendenciaController::class, 'resolver'])->middleware(ESCRITA_FLUXO)->name('resolver');
});

Route::middleware(['auth', EntradaMiddleware::class])->prefix('entrada')->name('entrada.')->group(function () {
    Route::get('/', [EntradaController::class, 'index'])->name('index');
    Route::patch('/{purchaseRequest}', [EntradaController::class, 'darEntrada'])->middleware(ESCRITA_FLUXO)->name('darEntrada');
});

require __DIR__.'/auth.php';
