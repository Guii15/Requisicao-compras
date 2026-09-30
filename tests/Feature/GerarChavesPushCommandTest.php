<?php

namespace Tests\Feature;

use App\Services\PushNotifier;
use Tests\TestCase;

class GerarChavesPushCommandTest extends TestCase
{
    public function test_imprime_as_chaves_no_formato_do_env(): void
    {
        $this->artisan('push:gerar-chaves')
            ->expectsOutputToContain('VAPID_PUBLIC_KEY=')
            ->expectsOutputToContain('VAPID_PRIVATE_KEY=')
            ->expectsOutputToContain('VAPID_SUBJECT=')
            ->assertSuccessful();
    }

    public function test_container_monta_o_notificador_com_e_sem_chaves(): void
    {
        config(['services.webpush.public_key' => null, 'services.webpush.private_key' => null]);
        $this->assertInstanceOf(PushNotifier::class, app(PushNotifier::class));

        $chaves = \Minishlink\WebPush\VAPID::createVapidKeys();
        config(['services.webpush.public_key' => $chaves['publicKey'], 'services.webpush.private_key' => $chaves['privateKey']]);
        $this->assertInstanceOf(PushNotifier::class, app(PushNotifier::class));
    }
}
