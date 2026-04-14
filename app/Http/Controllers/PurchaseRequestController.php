<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseRequestCreated;

class PurchaseRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseRequest::where('user_id', auth()->id());

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

        return view('requests.index', compact('requests'));
    }

    public function create()
    {
        return view('requests.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'requester_name' => 'required|string|max:255',
            'product_name'   => 'required|string|max:255',
            'product_code'   => 'nullable|string|max:100',
            'quantity'       => 'required|integer|min:1',
            'reason'         => 'required|string|max:255',
            'urgency'        => 'required|in:baixa,media,alta',
            'justification'  => 'required|string',
        ]);

        $purchaseRequest = PurchaseRequest::create([
            'user_id'        => Auth::id(),
            'requester_name' => $validated['requester_name'],
            'product_name'   => $validated['product_name'],
            'product_code'   => $validated['product_code'] ?? null,
            'quantity'       => $validated['quantity'],
            'reason'         => $validated['reason'],
            'urgency'        => $validated['urgency'],
            'justification'  => $validated['justification'],
            'status'         => 'pendente',
        ]);

        Mail::to('compras@suaempresa.com')->send(new PurchaseRequestCreated($purchaseRequest));

        return redirect()->route('requests.index')
            ->with('success', 'Requisição criada com sucesso!');
    }
}