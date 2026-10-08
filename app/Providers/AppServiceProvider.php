<?php

namespace App\Providers;

use App\Services\PushNotifier;
use App\Services\SaldoFornecedores;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Minishlink\WebPush\WebPush;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PushNotifier::class, function () {
            $config = config('services.webpush');

            if (empty($config['public_key']) || empty($config['private_key'])) {
                return new PushNotifier(null);
            }

            return new PushNotifier(new WebPush(['VAPID' => [
                'subject'    => $config['subject'],
                'publicKey'  => $config['public_key'],
                'privateKey' => $config['private_key'],
            ]], ['TTL' => 3600, 'urgency' => 'high']));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Datas por extenso (meses dos gráficos, etc.) em português; o resto do app continua com o idioma configurado.
        \Carbon\Carbon::setLocale('pt_BR');

        // Número da aba "Aguardando" do Financeiro, em todas as telas que mostram as abas.
        View::composer('financeiro._abas', fn ($view) => $view->with('qtdAguardando', app(SaldoFornecedores::class)->quantidadeAguardando()));
    }
}
