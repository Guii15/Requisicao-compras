<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Carbon\Carbon;

class MetricasController extends Controller
{
    public function index()
    {
        $tempoConferencia = $this->mediaEmHoras('aprovado_em', 'conferencia_concluida_em');
        $tempoEntrada = $this->mediaEmHoras('conferencia_concluida_em', 'entrada_concluida_em');
        $tempoCiclo = $this->mediaEmHoras('aprovado_em', 'entrada_concluida_em');

        $limite = now()->subHours(24);

        $estouradosConferencia = PurchaseRequest::where('status', 'aprovado')
            ->whereNotNull('aprovado_em')
            ->whereNull('status_conferencia')
            ->where('aprovado_em', '<=', $limite)
            ->orderBy('aprovado_em')
            ->get()
            ->map(fn ($r) => $this->itemEstourado($r, 'Aguardando conferência', $r->aprovado_em));

        $estouradosEntrada = PurchaseRequest::where('status', 'aprovado')
            ->whereIn('status_conferencia', ['conferido_ok', 'avancado_mesmo_assim'])
            ->whereNull('entrada_concluida_em')
            ->whereNotNull('conferencia_concluida_em')
            ->where('conferencia_concluida_em', '<=', $limite)
            ->orderBy('conferencia_concluida_em')
            ->get()
            ->map(fn ($r) => $this->itemEstourado($r, 'Aguardando entrada', $r->conferencia_concluida_em));

        $estourados = $estouradosConferencia->concat($estouradosEntrada)->sortBy('desde')->values();

        return view('metricas.index', compact('tempoConferencia', 'tempoEntrada', 'tempoCiclo', 'estourados'));
    }

    private function mediaEmHoras(string $campoInicio, string $campoFim): ?float
    {
        $registros = PurchaseRequest::whereNotNull($campoInicio)
            ->whereNotNull($campoFim)
            ->get([$campoInicio, $campoFim]);

        if ($registros->isEmpty()) {
            return null;
        }

        $totalHoras = $registros->sum(
            fn ($r) => $r->{$campoInicio}->diffInMinutes($r->{$campoFim}) / 60
        );

        return round($totalHoras / $registros->count(), 1);
    }

    private function itemEstourado(PurchaseRequest $r, string $etapa, Carbon $desde): array
    {
        return [
            'id' => $r->id,
            'product_name' => $r->product_name,
            'requester_name' => $r->requester_name,
            'etapa' => $etapa,
            'desde' => $desde,
            'horas_parado' => round($desde->diffInMinutes(now()) / 60, 1),
        ];
    }
}
