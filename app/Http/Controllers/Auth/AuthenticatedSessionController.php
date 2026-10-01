<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ConferenciaVisualizacaoMiddleware;
use App\Http\Middleware\ConferenteMiddleware;
use App\Http\Middleware\EntradaMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\VendedorMiddleware;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    private const ROTULOS_PERFIL = [
        'vendedor'    => 'Vendedor',
        'conferencia' => 'Conferência',
        'entrada'     => 'Entrada',
        'admin'       => 'Admin',
    ];

    /**
     * Display the login view for a specific perfil (vendedor, conferencia, entrada, admin).
     */
    public function create(string $perfil): View
    {
        return view('auth.login', ['perfil' => $perfil]);
    }

    /**
     * Display the login perfil picker (default /login).
     */
    public function escolha(): View
    {
        return view('auth.login-escolha');
    }

    /**
     * Handle an incoming authentication request. A conta precisa realmente ter o
     * perfil da URL usada (ex: /login/admin so' autentica quem e' admin de verdade) —
     * senao a pessoa acaba logada num painel diferente do que escolheu, o que confunde.
     */
    public function store(LoginRequest $request, string $perfil): RedirectResponse
    {
        $request->authenticate();

        if (!$this->perfilCorresponde(Auth::user(), $perfil)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Esta conta não tem acesso ao perfil ' . (self::ROTULOS_PERFIL[$perfil] ?? $perfil) . '.',
            ]);
        }

        $request->session()->regenerate();

        // Volta para a página que a pessoa tentou abrir, só se o perfil dela puder vê-la.
        // Senão ela caía direto num 403 (ex: celular que já abriu /admin, agora usado por um vendedor).
        $destino = $request->session()->pull('url.intended');

        if ($destino && $this->podeAbrir(Auth::user(), $destino)) {
            return redirect()->to($destino);
        }

        return redirect(route('dashboard', absolute: false));
    }

    /** Mesmas regras dos middlewares de perfil das rotas. */
    private function podeAbrir(User $user, string $url): bool
    {
        try {
            $rota = app('router')->getRoutes()->match(Request::create($url, 'GET'));
        } catch (\Throwable) {
            return false;
        }

        $regras = [
            AdminMiddleware::class                   => fn () => $user->isAdmin(),
            SuperAdminMiddleware::class              => fn () => $user->isSuperAdmin(),
            ConferenciaVisualizacaoMiddleware::class => fn () => $user->isConferente() || $user->isEntrada(),
            ConferenteMiddleware::class              => fn () => $user->isConferente(),
            EntradaMiddleware::class                 => fn () => $user->isEntrada(),
            VendedorMiddleware::class                => fn () => $user->isVendedor(),
        ];

        foreach ($rota->gatherMiddleware() as $middleware) {
            $classe = is_string($middleware) ? explode(':', $middleware)[0] : null;
            if ($classe && isset($regras[$classe]) && !$regras[$classe]()) {
                return false;
            }
        }

        return true;
    }

    private function perfilCorresponde(User $user, string $perfil): bool
    {
        return match ($perfil) {
            'admin'       => $user->isAdmin(),
            'conferencia' => $user->isConferente(),
            'entrada'     => $user->isEntrada(),
            'vendedor'    => $user->isVendedor(),
            default       => false,
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
