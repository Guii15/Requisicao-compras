<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acrescenta os cabeçalhos de segurança que o Laravel não envia por padrão.
 *
 * Cada um resolve um ataque específico — o comentário de cada bloco explica
 * qual. Nenhum deles muda o comportamento da aplicação: são instruções para
 * o navegador do usuário, não para o servidor.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Impede que o sistema seja carregado dentro de um <iframe> em outro
        // site. Sem isso, alguém monta uma página com o Requisição invisível
        // por cima de botões falsos e induz o usuário logado a clicar em ações
        // reais sem perceber (clickjacking).
        $response->headers->set('X-Frame-Options', 'DENY');

        // Impede o navegador de "adivinhar" o tipo de um arquivo pelo conteúdo.
        // Sem isso, um upload que o servidor declara como imagem mas que contém
        // HTML pode acabar sendo executado como página.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Não vaza a URL interna (com IDs de requisição, por exemplo) para
        // sites externos quando o usuário clica num link que sai do sistema.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Desliga APIs do navegador que este sistema não usa. Se um dia entrar
        // XSS aqui, o código injetado não consegue ligar câmera nem microfone.
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );

        // HSTS: manda o navegador só voltar neste domínio por HTTPS, mesmo que
        // o usuário digite http://. Protege contra interceptação na primeira
        // conexão em rede pública.
        //
        // Só é enviado quando a requisição já veio por HTTPS: mandar isso em
        // ambiente local (http://localhost) trava o navegador no domínio e
        // vira dor de cabeça para desenvolver.
        //
        // ATENÇÃO: 1 ano é irreversível na prática. Se o domínio perder o
        // certificado, o navegador de quem já acessou vai recusar a conexão
        // até o prazo vencer. O certificado é Let's Encrypt com renovação
        // automática, então o risco é baixo — mas precisa ser uma decisão
        // consciente, não um efeito colateral.
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
