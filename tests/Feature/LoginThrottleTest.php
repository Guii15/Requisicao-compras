<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Garante que o bloqueio de tentativas de login continua funcionando.
 *
 * Contexto: antes da correção de 09/09/2026, o bootstrap/app.php confiava em
 * qualquer proxy (trustProxies at '*'). Como o Laravel passa a ler o IP do
 * cabeçalho X-Forwarded-For nesse modo, e o throttle do login usa o IP na
 * chave, bastava variar esse cabeçalho a cada tentativa para nunca ser
 * bloqueado.
 *
 * O QUE ESTES TESTES NÃO COBREM
 *
 * A parte do trustProxies não dá para testar por aqui. Nos testes o Laravel
 * simula a requisição vindo de 127.0.0.1, que é justamente um proxy confiável
 * na configuração corrigida — então ele obedece o X-Forwarded-For que o próprio
 * teste mandou, e o ataque não se reproduz de dentro. Quem vem da internet
 * chega com IP público e tem o cabeçalho descartado.
 *
 * Ou seja: se alguém voltar o bootstrap/app.php para trustProxies(at: '*'),
 * estes testes continuam passando e o sistema fica vulnerável de novo.
 * A verificação dessa linha é manual — está na revisão de código, não aqui.
 */
class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // O contador do throttle vive no cache e é compartilhado entre testes.
        // Sem limpar, a ordem de execução mudaria o resultado.
        RateLimiter::clear('');
        $this->app['cache']->clear();
    }

    public function test_bloqueia_apos_muitas_tentativas_do_mesmo_ip(): void
    {
        $resposta = null;

        for ($i = 0; $i < 15; $i++) {
            $resposta = $this->post('/login/admin', [
                'email'    => 'naoexiste@teste.com',
                'password' => 'senha-errada',
            ]);
        }

        // 429 = Too Many Requests. Vem do middleware throttle:10,1.
        $resposta->assertStatus(429);
    }

    public function test_bloqueia_mesmo_variando_o_email(): void
    {
        // Este é o cenário de "password spraying": uma senha comum testada em
        // muitos e-mails. O limite do LoginRequest não pega, porque a chave
        // dele inclui o e-mail — cada e-mail tem contador próprio. Quem pega
        // é o throttle por IP.
        $resposta = null;

        for ($i = 0; $i < 15; $i++) {
            $resposta = $this->post('/login/admin', [
                'email'    => "usuario{$i}@teste.com",
                'password' => 'Senha123456',
            ]);
        }

        $resposta->assertStatus(429);
    }

    public function test_nao_bloqueia_uso_normal(): void
    {
        // Contraprova: o limite não pode atrapalhar quem está trabalhando.
        // Três tentativas erradas seguidas é algo que acontece todo dia.
        for ($i = 0; $i < 3; $i++) {
            $resposta = $this->post('/login/admin', [
                'email'    => 'pessoa@teste.com',
                'password' => 'errei-de-novo',
            ]);

            $resposta->assertStatus(302);   // volta pro formulário, não bloqueia
        }
    }
}
