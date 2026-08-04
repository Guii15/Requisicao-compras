<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Http\Request;

class PendenciaController extends Controller
{
    public function index()
    {
        $requests = PurchaseRequest::with(['user', 'conferente', 'fotosConferencia'])
            ->where('status', 'aprovado')
            ->where('status_conferencia', 'divergente')
            ->where('tipo_entrega', 'estoque')
            ->latest()
            ->paginate(15);

        return view('pendencias.index', compact('requests'));
    }
}
