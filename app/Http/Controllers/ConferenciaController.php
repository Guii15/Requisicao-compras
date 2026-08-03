<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Http\Request;

class ConferenciaController extends Controller
{
    public function index()
    {
        $requests = PurchaseRequest::with('user')
            ->where('status', 'aprovado')
            ->whereNull('status_conferencia')
            ->latest()
            ->paginate(15);

        return view('conferencia.index', compact('requests'));
    }
}
