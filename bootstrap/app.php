<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Confiar apenas no proxy reverso local (nginx do CloudPanel).
        //
        // Antes isto era trustProxies(at: '*'), que manda o Laravel aceitar o
        // cabeçalho X-Forwarded-For de QUALQUER origem. Como quem faz a
        // requisição escolhe esse cabeçalho, o $request->ip() passava a ser um
        // valor controlado pelo atacante — e o bloqueio de tentativas de login
        // usa justamente o IP na chave (ver LoginRequest::throttleKey).
        // Resultado: bastava variar o cabeçalho a cada tentativa para ganhar um
        // contador novo e nunca ser bloqueado.
        //
        // Se um dia o app passar a rodar atrás de Cloudflare ou de outro proxy
        // que não seja local, o IP dele precisa ser acrescentado nesta lista.
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1'],
            headers: Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                | Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
                | Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX,
        );

        // Cabeçalhos de segurança em todas as respostas web.
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
