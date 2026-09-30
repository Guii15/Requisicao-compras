<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DadosCompraController;
use App\Http\Controllers\ConferenciaController;
use App\Http\Controllers\PendenciaController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\FornecedorController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ConferenteMiddleware;
use App\Http\Middleware\ConferenciaVisualizacaoMiddleware;
use App\Http\Middleware\EntradaMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\VendedorMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Limites de requisição (throttle) nas rotas de escrita
|--------------------------------------------------------------------------
| Os números são por minuto, por usuário autenticado, e foram escolhidos MUITO
| acima do uso real (um vendedor abre ~20 requisições por DIA) porque o objetivo
| não é racionar trabalho — é impedir que um script em laço encha o banco.
| Limite que atrapalha quem está trabalhando é pior que limite nenhum, porque
| vira motivo para desligarem a proteção.
|
|   throttle:30,1   criação e edição pelo próprio usuário
|   throttle:60,1   conferência, pendência e entrada — quem opera esses painéis
|                   processa vários itens seguidos
|   throttle:10,1   mexe em conta de usuário; deve ser raro
|
| Os valores estão escritos direto em cada rota, e não em constantes: o arquivo
| de rotas é carregado uma vez por caso de teste, e um `const` no topo estoura
| com "already defined" na segunda carga.
*/

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
    Route::get('/requisicoes/{purchaseRequest}/editar', [PurchaseRequestController::class, 'edit'])->name('requests.edit');
    Route::patch('/requisicoes/{purchaseRequest}', [PurchaseRequestController::class, 'update'])->middleware('throttle:30,1')->name('requests.update');
    Route::get('/requisicoes/{purchaseRequest}/exportar', [PurchaseRequestController::class, 'export'])->name('requests.export');
});

// Criação de requisição: vendedor cria a própria, e admin também pode criar (em nome de um vendedor).
Route::middleware('auth')->group(function () {
    Route::get('/requisicoes/nova', [PurchaseRequestController::class, 'create'])->name('requests.create');
    Route::post('/requisicoes', [PurchaseRequestController::class, 'store'])->middleware('throttle:30,1')->name('requests.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/requisicoes/{purchaseRequest}/anexo', [PurchaseRequestController::class, 'baixarAnexo'])->name('requests.anexo');
    Route::get('/admin/compras/{purchaseRequest}/pedido', [DadosCompraController::class, 'baixarPedido'])->name('admin.compras.pedido');
});

Route::middleware('auth')->prefix('push')->name('push.')->group(function () {
    Route::post('/subscribe', [PushSubscriptionController::class, 'store'])->middleware('throttle:30,1')->name('subscribe');
    Route::delete('/subscribe', [PushSubscriptionController::class, 'destroy'])->middleware('throttle:30,1')->name('unsubscribe');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('throttle:10,1')->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('throttle:10,1')->name('profile.destroy');
});

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::patch('/requisicoes/{purchaseRequest}', [AdminController::class, 'update'])->middleware('throttle:60,1')->name('requests.update');
    Route::get('/requisicoes/{purchaseRequest}/exportar', [AdminController::class, 'export'])->name('requests.export');

    Route::get('/mensal/{year}/{month}', [AdminController::class, 'monthlyRequests'])->name('monthly');
    Route::get('/compras', [DadosCompraController::class, 'index'])->name('compras.index');
    Route::get('/compras-feitas', [DadosCompraController::class, 'feitas'])->name('compras.feitas');
    Route::get('/compras/{purchaseRequest}', [DadosCompraController::class, 'edit'])->name('compras.edit');
    Route::patch('/compras/{purchaseRequest}', [DadosCompraController::class, 'update'])->middleware('throttle:60,1')->name('compras.update');
    Route::get('/itens-mais-solicitados', [AdminController::class, 'itensMaisSolicitados'])->name('itens-mais-solicitados');
    Route::get('/historico-compras', [AdminController::class, 'historicoCompras'])->name('historico-compras');
    Route::get('/fornecedores', [FornecedorController::class, 'index'])->name('fornecedores.index');
    Route::get('/fornecedores/buscar', [FornecedorController::class, 'buscar'])->name('fornecedores.buscar');
    Route::post('/fornecedores/mesclar', [FornecedorController::class, 'mesclar'])->middleware('throttle:30,1')->name('fornecedores.mesclar');

    Route::middleware(SuperAdminMiddleware::class)->group(function () {
        Route::get('/usuarios', [AdminController::class, 'users'])->name('users.index');
        Route::post('/usuarios', [AdminController::class, 'storeUser'])->middleware('throttle:10,1')->name('users.store');
        Route::delete('/usuarios/{user}', [AdminController::class, 'destroyUser'])->middleware('throttle:10,1')->name('users.destroy');
        Route::patch('/usuarios/{user}/senha', [AdminController::class, 'resetPassword'])->middleware('throttle:10,1')->name('users.resetPassword');
        Route::patch('/usuarios/{user}/perfil', [AdminController::class, 'updateRole'])->middleware('throttle:10,1')->name('users.updateRole');
    });
});

Route::middleware(['auth'])->prefix('conferencia')->name('conferencia.')->group(function () {
    Route::get('/', [ConferenciaController::class, 'index'])->middleware(ConferenciaVisualizacaoMiddleware::class)->name('index');
    Route::patch('/{purchaseRequest}', [ConferenciaController::class, 'conferir'])->middleware([ConferenteMiddleware::class, 'throttle:60,1'])->name('conferir');
    Route::patch('/{purchaseRequest}/coleta', [ConferenciaController::class, 'registrarColeta'])->middleware([ConferenteMiddleware::class, 'throttle:60,1'])->name('coleta');
});

Route::middleware(['auth', AdminMiddleware::class])->prefix('pendencias')->name('pendencias.')->group(function () {
    Route::get('/', [PendenciaController::class, 'index'])->name('index');
    Route::patch('/{purchaseRequest}', [PendenciaController::class, 'resolver'])->middleware('throttle:60,1')->name('resolver');
});

Route::middleware(['auth', EntradaMiddleware::class])->prefix('entrada')->name('entrada.')->group(function () {
    Route::get('/', [EntradaController::class, 'index'])->name('index');
    Route::patch('/{purchaseRequest}', [EntradaController::class, 'darEntrada'])->middleware('throttle:60,1')->name('darEntrada');
});

require __DIR__.'/auth.php';
