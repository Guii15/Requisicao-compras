<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseRequestCreated;
use App\Http\Controllers\AdminController;

class PurchaseRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseRequest::where('user_id', auth()->id());

        if ($request->filled('requester_name')) {
            $query->where('requester_name', 'like', '%' . $request->requester_name . '%');
        }

        if ($request->filled('product_name')) {
            $query->where('product_name', 'like', '%' . $request->product_name . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        return view('requests.index', compact('requests'));
    }

    public function create()
    {
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
            'product_name'   => 'required|string|max:255',
            'product_code'   => 'nullable|string|max:100',
            'product_url'    => 'nullable|url|max:2048',
            'quantity'       => 'required|integer|min:1',
        ], [
            'requester_name.required' => 'O nome do vendedor é obrigatório.',
            'urgency.required'        => 'Selecione a urgência.',
            'reason.required'         => 'O motivo é obrigatório.',
            'product_name.required'   => 'O nome do produto é obrigatório.',
            'quantity.required'       => 'A quantidade é obrigatória.',
            'quantity.min'            => 'A quantidade mínima é 1.',
            'justification.required'  => 'O campo Obs é obrigatório.',
        ]);

        $purchaseRequest->update([
            'requester_name' => $request->requester_name,
            'supplier'       => $request->supplier,
            'urgency'        => $request->urgency,
            'reason'         => $request->reason,
            'justification'  => $request->justification,
            'product_name'   => $request->product_name,
            'product_code'   => $request->product_code,
            'product_url'    => $request->product_url,
            'quantity'       => $request->quantity,
        ]);

        return redirect()->route('requests.index')->with('success', 'Requisição atualizada com sucesso!');
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
        $request->validate([
            'requester_name'          => 'required|string|max:255',
            'supplier'                => 'nullable|string|max:255',
            'urgency'                 => 'required|in:baixa,media,alta',
            'reason'                  => 'required|string|max:255',
            'justification'           => 'required|string|max:500',
            'products'                => 'required|array|min:1',
            'products.*.product_name' => 'required|string|max:255',
            'products.*.product_code' => 'nullable|string|max:100',
            'products.*.quantity'     => 'required|integer|min:1',
        ], [
            'requester_name.required'          => 'O nome do vendedor é obrigatório.',
            'urgency.required'                 => 'Selecione a urgência.',
            'reason.required'                  => 'O motivo é obrigatório.',
            'products.required'                => 'Adicione pelo menos um produto.',
            'products.*.product_name.required' => 'Preencha o nome do produto em todos os itens.',
            'products.*.quantity.required'     => 'Preencha a quantidade em todos os itens.',
            'products.*.quantity.min'          => 'A quantidade mínima é 1.',
            'justification.required'           => 'O campo Obs é obrigatório.',
        ]);

        $created = [];

        foreach ($request->products as $product) {
            if (empty(trim($product['product_name'] ?? ''))) continue;

            $created[] = PurchaseRequest::create([
                'user_id'        => Auth::id(),
                'requester_name' => $request->requester_name,
                'supplier'       => $request->supplier,
                'urgency'        => $request->urgency,
                'reason'         => $request->reason,
                'justification'  => $request->justification,
                'product_name'   => $product['product_name'],
                'product_code'   => $product['product_code'] ?? null,
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
        return redirect()->route('requests.index')
            ->with('success', $count === 1 ? 'Requisição criada com sucesso!' : "{$count} requisições criadas com sucesso!");
    }
}
