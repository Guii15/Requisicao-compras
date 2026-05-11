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
        $userId = auth()->id();
        $query = PurchaseRequest::where('user_id', $userId);

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

        $stats = [
            'total'       => PurchaseRequest::where('user_id', $userId)->count(),
            'aprovado'    => PurchaseRequest::where('user_id', $userId)->where('status', 'aprovado')->count(),
            'pendente'    => PurchaseRequest::where('user_id', $userId)->where('status', 'pendente')->count(),
            'rejeitado'   => PurchaseRequest::where('user_id', $userId)->where('status', 'rejeitado')->count(),
            'total_gasto' => (float) PurchaseRequest::where('user_id', $userId)->where('status', 'aprovado')->sum('valor'),
        ];

        $topItems = PurchaseRequest::where('user_id', $userId)
            ->select('product_name')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('product_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $recentes = PurchaseRequest::where('user_id', $userId)
            ->latest()
            ->limit(6)
            ->get();

        return view('requests.index', compact('requests', 'stats', 'topItems', 'recentes'));
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
            'products.*.product_url'  => 'nullable|url|max:2048',
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
                'product_url'    => $product['product_url'] ?? null,
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
