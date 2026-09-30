<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GerarChavesPush extends Command
{
    protected $signature = 'push:gerar-chaves';

    protected $description = 'Gera um par de chaves VAPID para as notificações push (cole no .env do ambiente)';

    public function handle(): int
    {
        $chaves = VAPID::createVapidKeys();

        $this->info('Cole estas linhas no .env DESTE ambiente (cada ambiente tem o seu par; nunca vai pro git):');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY=' . $chaves['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $chaves['privateKey']);
        $this->line('VAPID_SUBJECT=mailto:ti@binariotecnologia.com');
        $this->newLine();
        $this->warn('Trocar a chave depois invalida as inscrições já feitas: todo mundo precisa ativar as notificações de novo.');

        return self::SUCCESS;
    }
}
