<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;

class EntradaController extends Controller
{
    public function index()
    {
        $requests = PurchaseRequest::with(['user', 'conferente', 'fotosConferencia'])
            ->whereIn('status_conferencia', ['conferido_ok', 'avancado_mesmo_assim'])
            ->whereNull('entrada_concluida_em')
            ->latest()
            ->paginate(15);

        return view('entrada.index', compact('requests'));
    }
}
