<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class PushNotifier
{
    /** Sem WebPush (chaves VAPID não configuradas) o serviço simplesmente não envia nada. */
    public function __construct(private readonly ?WebPush $webPush)
    {
    }

    public function aprovada(PurchaseRequest $item): void
    {
        $this->enviar(
            $this->conferentes(),
            'Nova requisição aprovada',
            $this->resumo($item, $item->requester_name),
            route('conferencia.index', [], false),
            'conferencia-' . $item->grupo_id
        );
    }

    public function conferida(PurchaseRequest $item): void
    {
        $seguePraEntrada = in_array($item->status_conferencia, ['conferido_ok', 'avancado_mesmo_assim'], true);

        if ($seguePraEntrada) {
            $this->enviar(
                $this->entrada(),
                'Item pronto para entrada',
                $this->resumo($item, ($item->quantidade_recebida ?? $item->quantity) . ' un. conferidas'),
                route('entrada.index', [], false),
                'entrada-' . $item->grupo_id
            );
        }

        $divergente = $item->status_conferencia === 'divergente';

        $this->enviar(
            $this->dono($item),
            $divergente ? 'Conferência com divergência' : 'Item conferido',
            $divergente
                ? $this->resumo($item, $item->observacao_conferencia ?: 'fale com a conferência')
                : $this->resumo($item, 'conferido, segue para a entrada'),
            route('requests.index', [], false),
            'vendedor-conferida-' . $item->grupo_id
        );
    }

    public function entradaConcluida(PurchaseRequest $item): void
    {
        $this->enviar(
            $this->dono($item),
            'Entrada concluída',
            $this->resumo($item, 'já deu entrada'),
            route('requests.index', [], false),
            'vendedor-entrada-' . $item->grupo_id
        );
    }

    /**
     * @param iterable<User> $usuarios
     */
    public function enviar(iterable $usuarios, string $titulo, string $texto, string $url, ?string $tag = null): void
    {
        if ($this->webPush === null) {
            return;
        }

        try {
            $ids = collect($usuarios)->pluck('id')->all();
            $inscricoes = PushSubscription::whereIn('user_id', $ids)->get();

            if ($inscricoes->isEmpty()) {
                return;
            }

            $payload = json_encode(['title' => $titulo, 'body' => $texto, 'url' => $url, 'tag' => $tag], JSON_UNESCAPED_UNICODE);

            foreach ($inscricoes as $inscricao) {
                $this->webPush->queueNotification(Subscription::create([
                    'endpoint'        => $inscricao->endpoint,
                    'publicKey'       => $inscricao->public_key,
                    'authToken'       => $inscricao->auth_token,
                    'contentEncoding' => $inscricao->content_encoding,
                ]), $payload);
            }

            foreach ($this->webPush->flush() as $relatorio) {
                if ($relatorio->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $relatorio->getEndpoint())->delete();
                } elseif (!$relatorio->isSuccess()) {
                    Log::warning('Falha ao enviar push: ' . $relatorio->getReason());
                }
            }
        } catch (Throwable $e) {
            Log::error('Falha ao enviar notificação push: ' . $e->getMessage());
        }
    }

    /** @return Collection<int, User> */
    private function conferentes(): Collection
    {
        return User::where('role', 'conferente')->get();
    }

    /** @return Collection<int, User> */
    private function entrada(): Collection
    {
        return User::where('role', 'entrada')->get();
    }

    /** @return Collection<int, User> */
    private function dono(PurchaseRequest $item): Collection
    {
        return collect([$item->user])->filter();
    }

    private function resumo(PurchaseRequest $item, ?string $detalhe): string
    {
        $texto = trim($item->product_name . ($detalhe ? ' — ' . $detalhe : ''));

        return Str::limit($texto, 120);
    }
}
