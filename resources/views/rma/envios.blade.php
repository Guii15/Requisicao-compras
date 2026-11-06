@extends('layouts.app')

{{-- Listagem usa a largura toda da tela (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')

@php
    use Illuminate\Support\Facades\Storage;
    $rotuloRma = 'display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;';
    $campoRma = 'width:100%; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:14px; box-sizing:border-box;';
    $corStatus = fn ($s) => match (true) {
        $s === 'Aguardando envio' => ['#fef3c7', '#92400e'],
        in_array($s, ['Enviado ao fornecedor', 'Em análise'], true) => ['#dbeafe', '#1e40af'],
        $s === 'Recusado' => ['#fee2e2', '#991b1b'],
        default => ['#dcfce7', '#166534'],
    };
@endphp

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">RMA</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Itens com defeito mandados pela Conferência e pela Entrada para devolver ao fornecedor</p>
    </div>

    @include('rma._abas', ['abaRma' => 'envios'])

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <div class="m-rolagem" style="display:flex; gap:6px; margin-bottom:14px;">
            @foreach(['abertos' => 'Em aberto', 'finalizados' => 'Finalizados', 'todos' => 'Todos'] as $valor => $rotulo)
                <a href="{{ route('rma.envios', array_filter(['situacao' => $valor === 'abertos' ? null : $valor, 'q' => $q !== '' ? $q : null])) }}"
                   style="padding:6px 14px; border-radius:9999px; font-size:13px; font-weight:600; text-decoration:none; white-space:nowrap;
                          background:{{ $situacao === $valor ? '#05018D' : '#f3f4f6' }}; color:{{ $situacao === $valor ? '#fff' : '#374151' }};">
                    {{ $rotulo }}@if($valor === 'abertos' && $abertos > 0) ({{ $abertos }})@endif
                </a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('rma.envios') }}" class="m-busca" style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end;">
            @if($situacao !== 'abertos')
                <input type="hidden" name="situacao" value="{{ $situacao }}">
            @endif
            <div style="flex:1; min-width:200px;">
                <label style="{{ $rotuloRma }}">Buscar</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="Produto, código, fornecedor, nº de série, protocolo ou rastreio..." style="{{ $campoRma }}">
            </div>
            <button type="submit" style="background:#05018D; color:#fff; padding:9px 20px; border:none; border-radius:7px; font-size:14px; font-weight:600; cursor:pointer; white-space:nowrap;">Buscar</button>
            @if($q !== '')
                <a href="{{ route('rma.envios', array_filter(['situacao' => $situacao !== 'abertos' ? $situacao : null])) }}" style="padding:9px 16px; border-radius:7px; border:1px solid #e5e7eb; color:#6b7280; text-decoration:none; font-size:14px; white-space:nowrap;">Limpar</a>
            @endif
        </form>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">✓ {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            @foreach($errors->all() as $erro)
                <div>{{ $erro }}</div>
            @endforeach
        </div>
    @endif

    @forelse($envios as $envio)
        @php $item = $envio->purchaseRequest; [$fundoSt, $corSt] = $corStatus($envio->status); @endphp
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; margin-bottom:14px; box-shadow:0 1px 4px rgba(0,0,0,0.04);">
            <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
                <div style="min-width:0;">
                    <div style="font-size:16px; font-weight:700; color:#111827; overflow-wrap:anywhere;">{{ $item->product_name }}</div>
                    <div style="font-size:13px; color:#6b7280; margin-top:2px;">
                        Req. #{{ $item->id }}
                        @if($item->product_code) · Cód. {{ $item->product_code }} @endif
                        · Fornecedor: <strong style="color:#374151;">{{ $item->supplier ?: '—' }}</strong>
                        @if($item->empresa) · Empresa: {{ $item->empresa }} @endif
                        · Compra: {{ $item->data_compra ? $item->data_compra->format('d/m/Y') : '—' }}
                        @if($item->pedido_compra_path)
                            · <a href="{{ route('admin.compras.pedido', $item) }}" target="_blank" style="color:#05018D; font-weight:600; text-decoration:underline;">📎 Pedido de compra</a>
                        @endif
                    </div>
                </div>
                <span style="background:{{ $fundoSt }}; color:{{ $corSt }}; padding:4px 12px; border-radius:20px; font-size:12.5px; font-weight:700; white-space:nowrap;">{{ $envio->status }}</span>
            </div>

            <div style="display:flex; gap:16px; flex-wrap:wrap; margin-top:12px; padding:12px 14px; background:#fef2f2; border-radius:8px;">
                <div style="flex:1; min-width:200px;">
                    <div style="font-size:11px; font-weight:700; color:#b8301a; text-transform:uppercase;">Defeito · {{ $envio->quantidade }} un.</div>
                    <div style="font-size:14px; color:#111827; margin-top:3px; white-space:pre-line; overflow-wrap:anywhere;">{{ $envio->defeito }}</div>
                    <div style="font-size:12px; color:#6b7280; margin-top:6px;">
                        Enviado por <strong>{{ $envio->enviadoPor?->name ?? '—' }}</strong> em {{ $envio->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                    </div>
                </div>
                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                    @if($envio->foto_path)
                        <a href="{{ Storage::url($envio->foto_path) }}" target="_blank" title="Foto do defeito">
                            <img src="{{ Storage::url($envio->foto_path) }}" alt="Foto do defeito" style="width:64px; height:64px; object-fit:cover; border-radius:6px; border:1px solid #fca5a5;">
                        </a>
                    @endif
                    <x-fotos-conferencia :item="$item" :tamanho="64" />
                </div>
            </div>

            <form method="POST" action="{{ route('rma.envios.update', $envio) }}" style="margin-top:14px;">
                @csrf
                @method('PATCH')
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
                    <div>
                        <label style="{{ $rotuloRma }}">Status</label>
                        <select name="status" style="{{ $campoRma }}">
                            @foreach(\App\Models\RmaEnvio::STATUS as $st)
                                <option value="{{ $st }}" @selected($envio->status === $st)>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="{{ $rotuloRma }}">Nº de série</label>
                        <input type="text" name="numero_serie" value="{{ $envio->numero_serie }}" maxlength="120" style="{{ $campoRma }}">
                    </div>
                    <div>
                        <label style="{{ $rotuloRma }}">Protocolo / nº do RMA no fornecedor</label>
                        <input type="text" name="protocolo_fornecedor" value="{{ $envio->protocolo_fornecedor }}" maxlength="120" style="{{ $campoRma }}">
                    </div>
                    <div>
                        <label style="{{ $rotuloRma }}">Enviado ao fornecedor em</label>
                        <input type="date" name="data_envio" value="{{ $envio->data_envio?->format('Y-m-d') }}" style="{{ $campoRma }}">
                    </div>
                    <div>
                        <label style="{{ $rotuloRma }}">Código de rastreio</label>
                        <input type="text" name="codigo_rastreio" value="{{ $envio->codigo_rastreio }}" maxlength="120" style="{{ $campoRma }}">
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <label style="{{ $rotuloRma }}">Observação</label>
                    <textarea name="observacao" rows="2" maxlength="2000" style="{{ $campoRma }}">{{ $envio->observacao }}</textarea>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:12px; flex-wrap:wrap;">
                    <span style="font-size:12px; color:#9ca3af;">
                        @if($envio->atualizadoPor)
                            Atualizado por {{ $envio->atualizadoPor->name }} em {{ $envio->updated_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                        @endif
                    </span>
                    <button type="submit" style="background:#05018D; color:#fff; padding:9px 22px; border:none; border-radius:7px; font-size:14px; font-weight:600; cursor:pointer;">Salvar</button>
                </div>
            </form>
        </div>
    @empty
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:40px 16px; text-align:center; color:#9ca3af; font-size:14px;">
            {{ $situacao === 'finalizados' ? 'Nenhum RMA finalizado.' : 'Nenhum item mandado ao RMA.' }}
        </div>
    @endforelse

    @if($envios->hasPages())
        <div style="margin-top:16px;">{{ $envios->links() }}</div>
    @endif

</div>

@endsection
