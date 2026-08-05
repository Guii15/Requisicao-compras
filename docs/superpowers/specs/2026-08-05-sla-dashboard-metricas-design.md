# SLA + Dashboard de Métricas

> Referência: `docs/PLANO-CONFERENCIA.md` §6 ("Métricas — fase futura").
> Último item da lista de pendências levantada após o sub-projeto 8 (Tela
> de Entrada), junto com a UI de atribuir perfil (já implementada) e a
> coluna Foto na Tela do Vendedor (já implementada).

## 1. Objetivo desta fatia

Hoje não existe nenhuma forma de medir quanto tempo cada etapa do fluxo
(aprovação → conferência → entrada) está levando, nem de identificar itens
parados. Esta fatia adiciona os dois timestamps que faltam pra calcular
essas métricas, e uma tela nova que mostra os números e uma lista de itens
"estourados" (parados numa etapa há tempo demais).

## 2. Contexto relevante do sistema atual

- `PurchaseRequest` já tem `status` (pendente/aprovado/rejeitado),
  `status_conferencia` (null/conferido_ok/divergente/avancado_mesmo_assim/
  cancelado) e `entrada_concluida_em` (timestamp, sub-projeto 8). Não tem
  nenhum timestamp de quando virou "aprovado" nem de quando a conferência
  foi concluída — só existe o `updated_at` genérico do Eloquent, que não
  serve pra isso porque a linha pode ser tocada de novo depois por outros
  motivos (ex: `PendenciaController::resolver()` atualiza `admin_note` e
  `status_conferencia` de um item já conferido antes, sobrescrevendo
  `updated_at`).
- `AdminController::update()` (`app/Http/Controllers/AdminController.php:90-132`)
  já tem o ponto exato onde a aprovação acontece pela primeira vez:
  ```php
  if ($request->status === 'aprovado' && $oldStatus !== 'aprovado') {
      // dispara e-mail pra ENTRADA_EMAIL
  }
  ```
  Esse é o lugar certo pra gravar `aprovado_em`.
- `ConferenciaController::conferir()` (`app/Http/Controllers/ConferenciaController.php:43-93`)
  já tem uma trava no início (`abort(409, ...)` se `status_conferencia !==
  null`) que garante que esse método só roda uma vez por item — é o lugar
  certo pra gravar `conferencia_concluida_em`, sem precisar de trava extra.
- **Fora de escopo, decidido no brainstorming**: `entrada_iniciada_em`
  (timestamp de quando alguém abre o item pela primeira vez na Tela de
  Entrada, mencionado no `PLANO-CONFERENCIA.md` §4.3) fica de fora. Nenhuma
  das 4 fórmulas de métrica do §6 depende dele, e implementá-lo exigiria um
  mecanismo de chamada ao servidor sem recarregar página que não existe em
  nenhuma tela hoje.
- Dados históricos: requisições aprovadas/conferidas antes desta fatia não
  terão `aprovado_em`/`conferencia_concluida_em` preenchidos — as médias só
  vão considerar itens que passaram por essas etapas depois do deploy desta
  fatia.

## 3. Decisões tomadas no brainstorming

1. **Dois timestamps novos, não três**: `aprovado_em` e
   `conferencia_concluida_em`. `entrada_concluida_em` já existe.
2. **Acesso ao dashboard**: qualquer usuário autenticado, sem restrição de
   papel — link visível no menu pra todo mundo.
3. **Limite de "estourado"**: 24 horas sem avançar de etapa.
4. **Duas etapas monitoradas** pra fins de "estourado":
   - Aguardando conferência: `aprovado_em` preenchido, `status_conferencia`
     nulo, e mais de 24h desde `aprovado_em`.
   - Aguardando entrada: `status_conferencia` em
     (`conferido_ok`,`avancado_mesmo_assim`), `entrada_concluida_em` nulo, e
     mais de 24h desde `conferencia_concluida_em`.
   (Itens `divergente` aguardando decisão em Pendências não entram nessa
   lista nesta fatia — não é uma etapa cronometrada pelas fórmulas do
   PLANO, e cancelar/aceitar em Pendências não tem prazo definido.)
5. **Métricas mostradas** (médias em horas, com 1 casa decimal, calculadas
   só sobre itens com os dois timestamps do intervalo preenchidos):
   - Tempo médio de conferência = média de (`conferencia_concluida_em` −
     `aprovado_em`)
   - Tempo médio de entrada = média de (`entrada_concluida_em` −
     `conferencia_concluida_em`)
   - Tempo total do ciclo = média de (`entrada_concluida_em` −
     `aprovado_em`)
   - Quando não há itens suficientes pra calcular uma média, mostra "Sem
     dados suficientes" em vez de dividir por zero.

## 4. Mudanças

### 4.1 Migration

Nova migration `add_sla_timestamps_to_purchase_requests_table`:
```php
Schema::table('purchase_requests', function (Blueprint $table) {
    $table->timestamp('aprovado_em')->nullable()->after('status');
    $table->timestamp('conferencia_concluida_em')->nullable()->after('conferente_id');
});
```
Com `down()` revertendo (`dropColumn(['aprovado_em', 'conferencia_concluida_em'])`).

### 4.2 `app/Models/PurchaseRequest.php`

Adiciona `aprovado_em` e `conferencia_concluida_em` ao `$fillable`, e um
cast `datetime` pros dois (mesmo padrão já usado pra `entrada_concluida_em`
desde o sub-projeto 8, que corrigiu um bug real de formatação sem esse
cast):
```php
protected $casts = [
    'entrada_concluida_em' => 'datetime',
    'aprovado_em' => 'datetime',
    'conferencia_concluida_em' => 'datetime',
];
```

### 4.3 `app/Http/Controllers/AdminController.php` — gravar `aprovado_em`

Dentro do bloco já existente `if ($request->status === 'aprovado' &&
$oldStatus !== 'aprovado')`, antes ou junto do update principal, adiciona
`'aprovado_em' => now()` ao array passado pra `$purchaseRequest->update()`.
Como esse update já roda condicionalmente uma única vez (o `if` já garante
que só entra quando o status MUDA para aprovado), o jeito mais simples é
incluir o campo no update principal com um valor condicional:
```php
$purchaseRequest->update([
    'status'      => $request->status,
    'admin_note'  => $request->admin_note,
    'valor'       => $request->valor ?: null,
    'supplier'    => $supplier,
    'aprovado_em' => ($request->status === 'aprovado' && $oldStatus !== 'aprovado')
        ? now()
        : $purchaseRequest->aprovado_em,
]);
```
(Preserva o valor já existente se o status não estiver virando aprovado
agora, pra não apagar um `aprovado_em` de uma aprovação anterior caso o
admin edite outros campos depois.)

### 4.4 `app/Http/Controllers/ConferenciaController.php` — gravar `conferencia_concluida_em`

No array de `update()` dentro de `conferir()`, adiciona
`'conferencia_concluida_em' => now()`.

### 4.5 `app/Http/Controllers/MetricasController.php` (novo)

```php
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

        $estouradosConferencia = PurchaseRequest::whereNotNull('aprovado_em')
            ->whereNull('status_conferencia')
            ->where('aprovado_em', '<=', $limite)
            ->orderBy('aprovado_em')
            ->get()
            ->map(fn ($r) => $this->itemEstourado($r, 'Aguardando conferência', $r->aprovado_em));

        $estouradosEntrada = PurchaseRequest::whereIn('status_conferencia', ['conferido_ok', 'avancado_mesmo_assim'])
            ->whereNull('entrada_concluida_em')
            ->whereNotNull('conferencia_concluida_em')
            ->where('conferencia_concluida_em', '<=', $limite)
            ->orderBy('conferencia_concluida_em')
            ->get()
            ->map(fn ($r) => $this->itemEstourado($r, 'Aguardando entrada', $r->conferencia_concluida_em));

        $estourados = $estouradosConferencia->concat($estouradosEntrada)->sortBy('desde');

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
```

### 4.6 `routes/web.php`

Dentro do grupo `Route::middleware('auth')->group(...)` (o mesmo grupo
genérico que hoje só tem as rotas de perfil, já que esta tela não tem
restrição de papel):
```php
Route::get('/metricas', [MetricasController::class, 'index'])->name('metricas.index');
```

### 4.7 `resources/views/metricas/index.blade.php` (novo)

Mesmo padrão visual das outras telas (cards de estatística estilo Admin
index, tabela desktop + cards mobile pra lista de estourados, mesmo
`@media (max-width: 768px)` já usado em todo o app). Três cards no topo
(Tempo médio de conferência / Tempo médio de entrada / Tempo total do
ciclo), cada um mostrando `{{ $valor !== null ? $valor.'h' : 'Sem dados
suficientes' }}`. Abaixo, tabela "Requisições Estouradas" com colunas
Produto / Vendedor / Etapa / Parado desde / Tempo parado, mensagem
"Nenhuma requisição estourada no momento" quando a lista está vazia.

### 4.8 `resources/views/layouts/navigation.blade.php`

Novo link "📊 Métricas" nos blocos desktop e mobile, **sem** condição de
papel (visível pra qualquer `Auth::user()`) — diferente de todos os outros
links do menu, que são condicionados a algum papel específico.

## 5. Fora de escopo

- `entrada_iniciada_em` / tempo de fila até alguém abrir o item — decidido
  no brainstorming, nenhuma fórmula do PLANO depende disso.
- Filtro de período (ex: "últimos 30 dias") no dashboard — mostra sempre o
  histórico completo desde que os timestamps passaram a existir.
- Gráficos ou visualizações além dos cards de número e da tabela de
  estourados.
- Notificação/alerta automático quando um item estoura — a tela só mostra
  a lista, não dispara nada.
- Itens `divergente` aguardando Pendências não entram na lista de
  estourados nesta fatia (não são uma etapa cronometrada pelas fórmulas do
  PLANO).

## 6. Plano de teste (local apenas)

1. Feature tests em `tests/Feature/MetricasControllerTest.php` (novo):
   - Acesso: guest redireciona pro login; qualquer usuário autenticado
     (vendedor comum, conferente, entrada, admin) consegue acessar
     `metricas.index` com 200.
   - Cálculo de tempo médio de conferência com 2+ itens de durações
     conhecidas retorna a média certa em horas.
   - Cálculo retorna `null`/"Sem dados suficientes" quando não há nenhum
     item com os dois timestamps preenchidos.
   - Item aprovado há mais de 24h sem `status_conferencia` aparece na lista
     de estourados como "Aguardando conferência".
   - Item conferido (OK ou avançado) há mais de 24h sem
     `entrada_concluida_em` aparece como "Aguardando entrada".
   - Item aprovado há menos de 24h NÃO aparece como estourado.
   - Item `divergente` (aguardando Pendências) NÃO aparece na lista de
     estourados, mesmo há mais de 24h.
2. Feature tests em `tests/Feature/ConferenciaControllerTest.php` e
   `tests/Feature/PurchaseRequestControllerTest.php` (arquivos já
   existentes): `conferir()` grava `conferencia_concluida_em`;
   `AdminController::update()` grava `aprovado_em` só na primeira vez que
   vira aprovado, preservando o valor em edições posteriores.
3. Confirmar visualmente em local: aprovar uma requisição, conferir ela,
   dar entrada, ver os 3 tempos aparecerem no dashboard depois.

Nenhum teste em produção ou em ambiente de staging nesta fatia.
