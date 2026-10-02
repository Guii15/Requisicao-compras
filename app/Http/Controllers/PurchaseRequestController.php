<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Mail\PurchaseRequestCreated;
use App\Http\Controllers\AdminController;
use App\Services\FornecedorResolver;
use App\Support\AgrupaRequisicoesPorGrupoId;
use App\Support\BuscaCaseInsensitive;

class PurchaseRequestController extends Controller
{
    use AgrupaRequisicoesPorGrupoId, BuscaCaseInsensitive;

    private const DISCO_ANEXO = 'local';

    public function index(Request $request)
    {
        $query = PurchaseRequest::where('user_id', auth()->id());

        if ($request->filled('requester_name')) {
            $this->whereLikeInsensitive($query, 'requester_name', $request->requester_name);
        }

        if ($request->filled('product_name')) {
            $this->whereLikeInsensitive($query, 'product_name', $request->product_name);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $requests = $this->paginarAgrupadoPorGrupoId($query, 15, 'page', ['fotosConferencia'])->withQueryString();

        $userId = auth()->id();

        $stats = [
            'total'       => PurchaseRequest::where('user_id', $userId)->count(),
            'pendente'    => PurchaseRequest::where('user_id', $userId)->where('status', 'pendente')->count(),
            'aprovado'    => PurchaseRequest::where('user_id', $userId)->where('status', 'aprovado')->count(),
            'rejeitado'   => PurchaseRequest::where('user_id', $userId)->where('status', 'rejeitado')->count(),
            'total_gasto' => (float) PurchaseRequest::where('user_id', $userId)->where('status', 'aprovado')->sum('valor'),
        ];

        $monthlySpending = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            return [
                'label' => $date->translatedFormat('M/y'),
                'total' => (float) PurchaseRequest::where('status', 'aprovado')
                    ->whereNotNull('valor')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('valor'),
            ];
        });

        // Os rankings juntam o mesmo nome escrito de formas diferentes (Yhan, YHAN, "Yhan ").
        $vendorSpending = \App\Support\RankingPorNome::agrupar(
            PurchaseRequest::select('requester_name')
                ->selectRaw('SUM(valor) as total_gasto')
                ->where('status', 'aprovado')
                ->whereNotNull('valor')
                ->groupBy('requester_name')
                ->get(),
            'requester_name'
        );

        $supplierSpending = \App\Support\RankingPorNome::agrupar(
            PurchaseRequest::select('supplier')
                ->selectRaw('SUM(valor) as total_gasto')
                ->where('status', 'aprovado')
                ->whereNotNull('valor')
                ->whereNotNull('supplier')
                ->where('supplier', '!=', '')
                ->groupBy('supplier')
                ->get(),
            'supplier',
            fn ($nome) => \App\Models\Fornecedor::normalizar($nome)
        );

        return view('requests.index', compact('requests', 'stats', 'monthlySpending', 'vendorSpending', 'supplierSpending'));
    }

    public function create()
    {
        if (!auth()->user()->isVendedor() && !auth()->user()->isAdmin()) {
            abort(403, 'Acesso restrito.');
        }

        $userId = auth()->id();

        $stats = [
            'total'    => PurchaseRequest::where('user_id', $userId)->count(),
            'pendente' => PurchaseRequest::where('user_id', $userId)->where('status', 'pendente')->count(),
            'aprovado' => PurchaseRequest::where('user_id', $userId)->where('status', 'aprovado')->count(),
        ];

        $recentes = PurchaseRequest::where('user_id', $userId)->latest()->limit(4)->get();

        return view('requests.create', compact('stats', 'recentes'));
    }

    public function edit(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->user_id !== auth()->id()) {
            abort(403);
        }

        if ($purchaseRequest->status !== 'pendente') {
            return redirect()->route('requests.index')->with('error', 'Só é possível editar requisições pendentes.');
        }

        return view('requests.edit', compact('purchaseRequest'));
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->user_id !== auth()->id()) {
            abort(403);
        }

        if ($purchaseRequest->status !== 'pendente') {
            return redirect()->route('requests.index')->with('error', 'Só é possível editar requisições pendentes.');
        }

        $request->validate([
            'requester_name' => 'required|string|max:255',
            'supplier'       => 'nullable|string|max:255',
            'urgency'        => 'required|in:baixa,media,alta',
            'reason'         => 'required|string|max:255',
            'justification'  => 'required|string|max:500',
            'tipo_entrega'   => 'required|in:estoque,entrega_direta',
            'product_name'   => 'required|string|max:255',
            'product_code'   => 'nullable|string|max:100',
            'product_url'    => 'nullable|url|max:2048',
            'quantity'       => 'required|integer|min:1',
            'anexo'          => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ], [
            'requester_name.required' => 'O nome do vendedor é obrigatório.',
            'urgency.required'        => 'Selecione a urgência.',
            'reason.required'         => 'O motivo é obrigatório.',
            'tipo_entrega.required'   => 'Selecione o tipo de entrega.',
            'tipo_entrega.in'         => 'Tipo de entrega inválido.',
            'product_name.required'   => 'O nome do produto é obrigatório.',
            'quantity.required'       => 'A quantidade é obrigatória.',
            'quantity.min'            => 'A quantidade mínima é 1.',
            'justification.required'  => 'O campo Obs é obrigatório.',
            'anexo.mimes'              => 'O anexo precisa ser PDF ou imagem (JPG, PNG, WEBP).',
            'anexo.max'                => 'O anexo pode ter no máximo 10 MB.',
        ]);

        $fornecedor = app(FornecedorResolver::class)->exato($request->supplier);

        $atualizacao = [
            'requester_name' => $request->requester_name,
            'supplier'       => $fornecedor?->nome ?? $request->supplier,
            'fornecedor_id'  => $fornecedor?->id,
            'supplier_original' => $request->supplier ?: null,
            'urgency'        => $request->urgency,
            'reason'         => $request->reason,
            'justification'  => $request->justification,
            'tipo_entrega'   => $request->tipo_entrega,
            'product_name'   => $request->product_name,
            'product_code'   => $request->product_code,
            'product_url'    => $request->product_url,
            'quantity'       => $request->quantity,
        ];

        if ($request->hasFile('anexo')) {
            $arquivo = $request->file('anexo');
            $caminhoAntigo = $purchaseRequest->anexo_path;

            $atualizacao['anexo_path'] = $arquivo->store('anexos-requisicao', self::DISCO_ANEXO);
            $atualizacao['anexo_nome'] = $arquivo->getClientOriginalName();

            if ($caminhoAntigo && !$purchaseRequest->arquivoUsadoPorOutro('anexo_path', $caminhoAntigo)) {
                \App\Support\LixeiraDeArquivos::descartar(self::DISCO_ANEXO, $caminhoAntigo);
            }
        }

        $purchaseRequest->update($atualizacao);

        return redirect()->route('requests.index')->with('success', 'Requisição atualizada com sucesso!');
    }

    /** O vendedor tira o anexo da própria requisição (só enquanto está pendente, como na edição). */
    public function removerAnexo(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->user_id !== auth()->id()) {
            abort(403);
        }

        if ($purchaseRequest->status !== 'pendente') {
            return redirect()->route('requests.index')->with('error', 'Só é possível editar requisições pendentes.');
        }

        if (!$purchaseRequest->anexo_path) {
            return back()->with('aviso', 'Esta requisição não tem anexo.');
        }

        $purchaseRequest->removerArquivo('anexo_path', 'anexo_nome', self::DISCO_ANEXO);

        return back()->with('success', 'Anexo removido.');
    }

    public function baixarAnexo(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $caminho = $purchaseRequest->anexo_path;

        if (!$caminho || !Storage::disk(self::DISCO_ANEXO)->exists($caminho)) {
            abort(404, 'Nenhum anexo encontrado.');
        }

        return Storage::disk(self::DISCO_ANEXO)->response($caminho, $purchaseRequest->anexo_nome ?? basename($caminho));
    }

    public function export(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->user_id !== auth()->id()) {
            abort(403);
        }

        $waLink = "https://wa.me/?text=" . rawurlencode(AdminController::buildWaText($purchaseRequest));
        return view('admin.export', ['req' => $purchaseRequest, 'waLink' => $waLink]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isVendedor() && !auth()->user()->isAdmin()) {
            abort(403, 'Acesso restrito.');
        }

        $request->validate([
            'requester_name'          => 'required|string|max:255',
            'supplier'                => 'nullable|string|max:255',
            'urgency'                 => 'required|in:baixa,media,alta',
            'reason'                  => 'required|string|max:255',
            'justification'           => 'required|string|max:500',
            'tipo_entrega'            => 'required|in:estoque,entrega_direta',
            'products'                => 'required|array|min:1',
            'products.*.product_name' => 'required|string|max:255',
            'products.*.product_code' => 'nullable|string|max:100',
            'products.*.product_url'  => 'nullable|string|max:2048',
            'products.*.quantity'     => 'required|integer|min:1',
            'products.*.anexo'        => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ], [
            'requester_name.required'          => 'O nome do vendedor é obrigatório.',
            'urgency.required'                 => 'Selecione a urgência.',
            'reason.required'                  => 'O motivo é obrigatório.',
            'tipo_entrega.required'            => 'Selecione o tipo de entrega.',
            'tipo_entrega.in'                   => 'Tipo de entrega inválido.',
            'products.required'                => 'Adicione pelo menos um produto.',
            'products.*.product_name.required' => 'Preencha o nome do produto em todos os itens.',
            'products.*.quantity.required'     => 'Preencha a quantidade em todos os itens.',
            'products.*.quantity.min'          => 'A quantidade mínima é 1.',
            'products.*.anexo.mimes'           => 'O anexo precisa ser PDF ou imagem (JPG, PNG, WEBP).',
            'products.*.anexo.max'             => 'O anexo pode ter no máximo 10 MB.',
            'justification.required'           => 'O campo Obs é obrigatório.',
        ]);

        $created = [];
        $grupoId = (string) Str::uuid();
        $fornecedor = app(FornecedorResolver::class)->exato($request->supplier);

        foreach ($request->products as $index => $product) {
            if (empty(trim($product['product_name'] ?? ''))) continue;

            $anexoPath = null;
            $anexoNome = null;

            if ($request->hasFile("products.{$index}.anexo")) {
                $arquivo = $request->file("products.{$index}.anexo");
                $anexoPath = $arquivo->store('anexos-requisicao', self::DISCO_ANEXO);
                $anexoNome = $arquivo->getClientOriginalName();
            }

            $created[] = PurchaseRequest::create([
                'user_id'        => Auth::id(),
                'grupo_id'       => $grupoId,
                'requester_name' => $request->requester_name,
                'supplier'       => $fornecedor?->nome ?? $request->supplier,
                'fornecedor_id'  => $fornecedor?->id,
                'supplier_original' => $request->supplier ?: null,
                'urgency'        => $request->urgency,
                'reason'         => $request->reason,
                'justification'  => $request->justification,
                'tipo_entrega'   => $request->tipo_entrega,
                'product_name'   => $product['product_name'],
                'product_code'   => $product['product_code'] ?? null,
                'product_url'    => $product['product_url'] ?? null,
                'anexo_path'     => $anexoPath,
                'anexo_nome'     => $anexoNome,
                'quantity'       => $product['quantity'],
                'status'         => 'pendente',
            ]);
        }

        if (!empty($created)) {
            try {
                Mail::to(env('COMPRAS_EMAIL'))->send(new PurchaseRequestCreated($created));
            } catch (\Exception $e) {
                \Log::error('Falha ao enfileirar e-mail de requisição: ' . $e->getMessage());
            }
        }

        $count = count($created);
        $destino = auth()->user()->isVendedor() ? route('requests.index') : route('admin.index');

        return redirect($destino)
            ->with('success', $count === 1 ? 'Requisição criada com sucesso!' : "{$count} requisições criadas com sucesso!");
    }
}
