@extends('layouts.app')

@section('content')

<div class="bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Minhas Requisições</h2>

        <a href="{{ route('requests.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            + Nova Requisição
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('requests.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 bg-gray-50 p-4 rounded border">
        <div>
            <label class="block text-sm font-medium mb-1">Vendedor</label>
            <input type="text"
                   name="requester_name"
                   value="{{ request('requester_name') }}"
                   class="w-full border rounded p-2"
                   placeholder="Nome do vendedor">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Data inicial</label>
            <input type="date"
                   name="date_from"
                   value="{{ request('date_from') }}"
                   class="w-full border rounded p-2">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Data final</label>
            <input type="date"
                   name="date_to"
                   value="{{ request('date_to') }}"
                   class="w-full border rounded p-2">
        </div>

        <div class="flex items-end gap-2">
            <button type="submit"
                    style="display:inline-block; background:#2563eb; color:#fff; padding:10px 16px; border:none; border-radius:6px; cursor:pointer;">
                Filtrar
            </button>

            <a href="{{ route('requests.index') }}"
               class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Limpar
            </a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Vendedor</th>
                    <th class="p-3 text-left">Produto</th>
                    <th class="p-3 text-left">Qtd</th>
                    <th class="p-3 text-left">Urgência</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Data</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr class="border-t">
                        <td class="p-3">{{ $req->requester_name ?? 'Não informado' }}</td>
                        <td class="p-3">{{ $req->product_name }}</td>
                        <td class="p-3">{{ $req->quantity }}</td>
                        <td class="p-3">
                            @if($req->urgency)
                                <span class="px-2 py-1 rounded text-white
                                    @if($req->urgency == 'alta') bg-red-500
                                    @elseif($req->urgency == 'media') bg-yellow-500
                                    @else bg-green-500 @endif">
                                    {{ ucfirst($req->urgency) }}
                                </span>
                            @else
                                <span class="text-gray-500">Não informado</span>
                            @endif
                        </td>
                        <td class="p-3">{{ ucfirst($req->status) }}</td>
                        <td class="p-3">{{ $req->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center p-4 text-gray-500">
                            Nenhuma requisição encontrada
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection