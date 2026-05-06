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
            'total'     => PurchaseRequest::count(),
            'pendente'  => PurchaseRequest::where('status', 'pendente')->count(),
            'aprovado'  => PurchaseRequest::where('status', 'aprovado')->count(),
            'rejeitado' => PurchaseRequest::where('status', 'rejeitado')->count(),
        ];

        return view('admin.index', compact('requests', 'stats'));
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'status'     => 'required|in:pendente,aprovado,rejeitado',
            'admin_note' => 'nullable|string|max:500',
        ]);

        $oldStatus = $purchaseRequest->status;

        $purchaseRequest->update([
            'status'     => $request->status,
            'admin_note' => $request->admin_note,
        ]);

        if ($request->status === 'aprovado' && $oldStatus !== 'aprovado') {
            $destinatarios = array_filter([env('ENTRADA_EMAIL'), env('ENTRADA_EMAIL_2')]);
            if (!empty($destinatarios)) {
                try {
                    Mail::to($destinatarios)->queue(new PurchaseRequestApproved($purchaseRequest));
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
            'password' => Hash::make($request->password),
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

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required'  => 'A nova senha é obrigatória.',
            'password.min'       => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', "Senha de {$user->name} redefinida com sucesso!");
    }
}
