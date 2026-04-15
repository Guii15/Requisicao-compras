<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
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
                Mail::to($destinatarios)->send(new PurchaseRequestApproved($purchaseRequest));
            }
        }

        return back()->with('success', 'Requisição atualizada com sucesso!');
    }
}
