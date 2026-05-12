<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseRequestApproved;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseRequest::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('requester_name')) {
            $query->where('requester_name', 'like', '%' . $request->requester_name . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $requests = $query->latest()->get();

        $stats = [
            'total'       => PurchaseRequest::count(),
            'pendente'    => PurchaseRequest::where('status', 'pendente')->count(),
            'aprovado'    => PurchaseRequest::where('status', 'aprovado')->count(),
            'rejeitado'   => PurchaseRequest::where('status', 'rejeitado')->count(),
            'total_gasto' => (float) PurchaseRequest::where('status', 'aprovado')->sum('valor'),
        ];

        $ranking = PurchaseRequest::select('requester_name')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = ? AND valor IS NOT NULL THEN valor ELSE 0 END) as total_gasto', ['aprovado'])
            ->groupBy('requester_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $vendorSpending = PurchaseRequest::select('requester_name')
            ->selectRaw('SUM(valor) as total_gasto')
            ->where('status', 'aprovado')
            ->whereNotNull('valor')
            ->groupBy('requester_name')
            ->orderByDesc('total_gasto')
            ->limit(10)
            ->get();

        $topItems = PurchaseRequest::select('product_name')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('product_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $weeklyStats = collect(range(4, 0))->map(function ($weeksAgo) {
            $start = now()->subWeeks($weeksAgo)->startOfWeek();
            $end   = now()->subWeeks($weeksAgo)->endOfWeek();
            return [
                'label' => 'S' . (5 - $weeksAgo),
                'total' => PurchaseRequest::whereBetween('created_at', [$start, $end])->count(),
            ];
        });

        return view('admin.index', compact('requests', 'stats', 'ranking', 'topItems', 'weeklyStats', 'vendorSpending'));
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'status'     => 'required|in:pendente,aprovado,rejeitado',
            'admin_note' => 'nullable|string|max:500',
            'valor'      => 'nullable|numeric|min:0',
        ]);

        $oldStatus = $purchaseRequest->status;

        $purchaseRequest->update([
            'status'     => $request->status,
            'admin_note' => $request->admin_note,
            'valor'      => $request->valor ?: null,
        ]);

        if ($request->status === 'aprovado' && $oldStatus !== 'aprovado') {
            $destinatarios = array_filter([env('ENTRADA_EMAIL'), env('ENTRADA_EMAIL_2')]);
            if (!empty($destinatarios)) {
                try {
                    Mail::to($destinatarios)->send(new PurchaseRequestApproved($purchaseRequest));
                } catch (\Exception $e) {
                    \Log::error('Falha ao enfileirar e-mail de aprovação: ' . $e->getMessage());
                }
            }
        }

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
            'is_admin'              => 'nullable|boolean',
        ], [
            'name.required'         => 'O nome é obrigatório.',
            'email.required'        => 'O e-mail é obrigatório.',
            'email.unique'          => 'Já existe um usuário com este e-mail.',
            'password.required'     => 'A senha é obrigatória.',
            'password.min'          => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed'    => 'As senhas não coincidem.',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'is_admin' => $request->boolean('is_admin'),
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