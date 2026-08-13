<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Support\AgrupaRequisicoesPorGrupoId;

class AdminController extends Controller
{
    use AgrupaRequisicoesPorGrupoId;

    public function index(Request $request)
    {
        $aba = $request->query('aba') === 'historico' ? 'historico' : 'pendentes';

        $query = PurchaseRequest::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

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

        // Um item "nao finalizado" ainda precisa de alguma acao (aprovacao ou entrada).
        // Um GRUPO fica em "Novas" enquanto tiver pelo menos um item nessa condicao;
        // so' passa pra "Historico" quando TODOS os itens do grupo estiverem finalizados.
        $grupoComItemNaoFinalizado = function ($sub) {
            $sub->select('grupo_id')->from('purchase_requests')->where(function ($condicao) {
                $condicao->where('status', 'pendente')
                    ->orWhere(function ($aprovadoSemEntrada) {
                        $aprovadoSemEntrada->where('status', 'aprovado')->whereNull('entrada_concluida_em');
                    });
            });
        };

        if ($aba === 'historico') {
            $query->whereNotIn('grupo_id', $grupoComItemNaoFinalizado);
        } else {
            $query->whereIn('grupo_id', $grupoComItemNaoFinalizado);
        }

        $requests = $this->paginarAgrupadoPorGrupoId($query, 15, 'page', ['user'])->withQueryString();

        $countPendentes = PurchaseRequest::whereIn('grupo_id', $grupoComItemNaoFinalizado)->distinct('grupo_id')->count('grupo_id');
        $countHistorico = PurchaseRequest::whereNotIn('grupo_id', $grupoComItemNaoFinalizado)->distinct('grupo_id')->count('grupo_id');

        $stats = [
            'total'       => PurchaseRequest::count(),
            'pendente'    => PurchaseRequest::where('status', 'pendente')->count(),
            'aprovado'    => PurchaseRequest::where('status', 'aprovado')->count(),
            'rejeitado'   => PurchaseRequest::where('status', 'rejeitado')->count(),
            'total_gasto' => (float) PurchaseRequest::where('status', 'aprovado')->sum('valor'),
        ];

        $vendorSpending = PurchaseRequest::select('requester_name')
            ->selectRaw('SUM(valor) as total_gasto')
            ->where('status', 'aprovado')
            ->whereNotNull('valor')
            ->groupBy('requester_name')
            ->orderByDesc('total_gasto')
            ->limit(10)
            ->get();

        $supplierSpending = PurchaseRequest::select('supplier')
            ->selectRaw('SUM(valor) as total_gasto')
            ->where('status', 'aprovado')
            ->whereNotNull('valor')
            ->whereNotNull('supplier')
            ->where('supplier', '!=', '')
            ->groupBy('supplier')
            ->orderByDesc('total_gasto')
            ->limit(10)
            ->get();

        $monthlySpending = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            return [
                'label' => $date->translatedFormat('M/y'),
                'year'  => $date->year,
                'month' => $date->month,
                'total' => (float) PurchaseRequest::where('status', 'aprovado')
                    ->whereNotNull('valor')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('valor'),
            ];
        });

        $supplierList = PurchaseRequest::whereNotNull('supplier')
            ->where('supplier', '!=', '')
            ->distinct()
            ->orderBy('supplier')
            ->pluck('supplier');

        return view('admin.index', compact('requests', 'stats', 'vendorSpending', 'supplierSpending', 'monthlySpending', 'supplierList', 'aba', 'countPendentes', 'countHistorico'));
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($request->filled('valor')) {
            $valor = $request->input('valor');
            // Converte formato brasileiro (8.640,00) para decimal (8640.00)
            if (str_contains($valor, ',')) {
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
                $request->merge(['valor' => $valor]);
            }
        }

        $request->validate([
            'status'     => 'required|in:pendente,aprovado,rejeitado',
            'admin_note' => 'nullable|string|max:2000',
            'valor'      => 'nullable|numeric|min:0',
            'supplier'   => 'nullable|string|max:255',
        ]);

        $supplier = $request->supplier ? mb_convert_case(mb_strtolower(trim($request->supplier)), MB_CASE_TITLE, 'UTF-8') : null;

        $purchaseRequest->update([
            'status'     => $request->status,
            'admin_note' => $request->admin_note,
            'valor'      => $request->valor ?: null,
            'supplier'   => $supplier,
        ]);

        return back()->with('success', 'Requisição atualizada com sucesso!');
    }

    public function users()
    {
        $users = User::orderBy('name')->get();
        return view('admin.users', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'perfil'                => 'required|in:vendedor,conferente,entrada,admin',
        ], [
            'name.required'         => 'O nome é obrigatório.',
            'email.required'        => 'O e-mail é obrigatório.',
            'email.unique'          => 'Já existe um usuário com este e-mail.',
            'password.required'     => 'A senha é obrigatória.',
            'password.min'          => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed'    => 'As senhas não coincidem.',
            'perfil.required'       => 'Selecione um perfil.',
            'perfil.in'             => 'Perfil inválido.',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'is_admin' => $request->perfil === 'admin',
            'role'     => in_array($request->perfil, ['conferente', 'entrada'], true) ? $request->perfil : null,
        ]);

        return back()->with('success', 'Usuário criado com sucesso!');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode excluir sua própria conta.');
        }

        $user->delete();
        return back()->with('success', 'Usuário removido com sucesso!');
    }

    public function updateRole(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode alterar seu próprio perfil.');
        }

        $request->validate([
            'perfil' => 'required|in:vendedor,conferente,entrada,admin',
        ], [
            'perfil.required' => 'Selecione um perfil.',
            'perfil.in'        => 'Perfil inválido.',
        ]);

        $user->update([
            'is_admin' => $request->perfil === 'admin',
            'role'     => in_array($request->perfil, ['conferente', 'entrada'], true) ? $request->perfil : null,
        ]);

        return back()->with('success', 'Perfil atualizado com sucesso!');
    }

    public static function buildWaText(PurchaseRequest $req): string
    {
        $urgencyLabel = ['baixa' => 'Baixa', 'media' => 'Media', 'alta' => 'Alta'][$req->urgency] ?? ucfirst($req->urgency);

        return "*REQUISICAO DE COMPRA #" . str_pad($req->id, 5, '0', STR_PAD_LEFT) . "*\n"
            . "Data: " . $req->created_at->format('d/m/Y') . "\n"
            . "Vendedor: " . $req->requester_name . "\n"
            . "Fornecedor: " . ($req->supplier ?: '-') . "\n"
            . "Urgencia: " . $urgencyLabel . "\n\n"
            . "*PRODUTO:*\n"
            . "- " . strtoupper($req->product_name)
            . ($req->product_code ? " (" . $req->product_code . ")" : "")
            . " - Qtd: " . number_format($req->quantity, 0, ',', '.') . "\n\n"
            . "*Motivo:* " . $req->reason . "\n"
            . "*Obs:* " . $req->justification;
    }

    public function monthlyRequests($year, $month)
    {
        $requests = PurchaseRequest::where('status', 'aprovado')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderByDesc('valor')
            ->get();

        return response()->json([
            'requests' => $requests->map(fn($r) => [
                'requester_name' => $r->requester_name,
                'product_name'   => $r->product_name,
                'supplier'       => $r->supplier ?: '—',
                'valor_fmt'      => $r->valor ? 'R$ ' . number_format($r->valor, 2, ',', '.') : '—',
                'quantity'       => $r->quantity,
            ]),
            'total_fmt' => 'R$ ' . number_format($requests->sum('valor'), 2, ',', '.'),
            'count'     => $requests->count(),
        ]);
    }

    public function export(PurchaseRequest $purchaseRequest)
    {
        $waLink = "https://wa.me/?text=" . rawurlencode(self::buildWaText($purchaseRequest));
        return view('admin.export', ['req' => $purchaseRequest, 'waLink' => $waLink]);
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required'  => 'A nova senha é obrigatória.',
            'password.min'       => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
        ]);

        $user->update(['password' => $request->password]);

        return back()->with('success', "Senha de {$user->name} redefinida com sucesso!");
    }
}
