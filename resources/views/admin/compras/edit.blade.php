@extends('layouts.app')

@section('content')

@php
    $labelStyle = 'display:block; font-size:12.5px; font-weight:600; color:#374151; margin-bottom:6px;';
    $inputStyle = 'width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; box-sizing:border-box;';
    $erroStyle  = 'color:#b91c1c; font-size:12px; margin-top:4px;';
    $precoInicial = old('preco_unitario', $item->preco_unitario !== null ? number_format($item->preco_unitario, 2, ',', '.') : '');
    $precoCaixaInicial = old('preco_caixa', $item->preco_caixa !== null ? number_format($item->preco_caixa, 2, ',', '.') : '');
    $valorInicial = old('valor', $item->valor !== null ? number_format($item->valor, 2, ',', '.') : '');
@endphp

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Painel Administrativo</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Gerencie todas as requisições de compra</p>
    </div>

    @include('admin._abas')

    <a href="{{ route('admin.compras.index') }}" style="font-size:13px; color:#6b7280; text-decoration:none;">← Voltar para Compras</a>

    <div class="m-uma-coluna" style="display:grid; grid-template-columns:minmax(0,2fr) minmax(260px,1fr); gap:20px; margin-top:12px; align-items:start;">

        {{-- Formulário --}}
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
            <h2 style="margin:0 0 4px; font-size:18px; font-weight:700; color:#111827;">Dados da compra</h2>
            <p style="margin:0 0 20px; color:#6b7280; font-size:13px;">{{ $item->product_name }} — {{ $item->quantity }} un.</p>

            @if(session('success'))
                <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:14px;">
                    ✓ {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.compras.update', $item) }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
                    <div>
                        <label style="{{ $labelStyle }}">Data da compra <span style="color:#ef4444;">*</span></label>
                        <input type="date" name="data_compra" required value="{{ old('data_compra', $item->data_compra?->format('Y-m-d')) }}" style="{{ $inputStyle }}">
                        @error('data_compra') <div style="{{ $erroStyle }}">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Fornecedor <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="supplier" required value="{{ old('supplier', $item->supplier) }}" style="{{ $inputStyle }}">
                        @error('supplier') <div style="{{ $erroStyle }}">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label style="{{ $labelStyle }}">Código do produto no fornecedor</label>
                        <input type="text" name="codigo_fornecedor" value="{{ old('codigo_fornecedor', $item->codigo_fornecedor) }}" style="{{ $inputStyle }}">
                        @error('codigo_fornecedor') <div style="{{ $erroStyle }}">{{ $message }}</div> @enderror
                    </div>

                    @php
                        $quantidadeTravada = $item->status_conferencia !== null || $item->quantidade_original !== null || $item->restante_de_id !== null;
                    @endphp
                    <div>
                        <label style="{{ $labelStyle }}">Quantidade comprada <span style="color:#ef4444;">*</span></label>
                        <input type="number" name="quantity" min="1" step="1" inputmode="numeric" required
                               value="{{ old('quantity', $item->quantity) }}" @if($quantidadeTravada) readonly @endif
                               style="{{ $inputStyle }} {{ $quantidadeTravada ? 'background:#f3f4f6; color:#6b7280;' : 'font-weight:700;' }}">
                        @if($item->status_conferencia !== null)
                            <div style="color:#6b7280; font-size:12px; margin-top:4px;">Este item já foi conferido; a quantidade não pode mais ser alterada.</div>
                        @elseif($quantidadeTravada)
                            <div style="color:#6b7280; font-size:12px; margin-top:4px;">Recebimento parcial: ajuste pelo botão Editar da Conferência.</div>
                        @else
                            <div style="color:#6b7280; font-size:12px; margin-top:4px;">Se comprou diferente do pedido, corrija aqui: a Conferência passa a ver este número. Se digita o preço total, confira se continua certo.</div>
                        @endif
                        @error('quantity') <div style="{{ $erroStyle }}">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Preço unitário (R$) <span style="color:#ef4444;">*</span></label>
                        <input type="text" inputmode="decimal" name="preco_unitario" required placeholder="0,00" value="{{ $precoInicial }}" style="{{ $inputStyle }}">
                        @error('preco_unitario') <div style="{{ $erroStyle }}">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label style="{{ $labelStyle }}">Preço da caixa (R$) <span style="color:#9ca3af; font-weight:400;">(opcional, quando comprado fechado)</span></label>
                        <input type="text" inputmode="decimal" name="preco_caixa" placeholder="0,00" value="{{ $precoCaixaInicial }}" style="{{ $inputStyle }}">
                        @error('preco_caixa') <div style="{{ $erroStyle }}">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label style="{{ $labelStyle }}">Preço total (R$)</label>
                        <input type="text" inputmode="decimal" name="valor" placeholder="0,00" value="{{ $valorInicial }}" style="{{ $inputStyle }} font-weight:700;">
                        @error('valor') <div style="{{ $erroStyle }}">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div style="margin-top:16px;">
                    <label style="{{ $labelStyle }}">Pedido de compra (PDF ou imagem, até 10 MB)</label>
                    @if($item->pedido_compra_path)
                        <div style="font-size:13px; margin-bottom:8px;">
                            Anexado: <a href="{{ route('admin.compras.pedido', $item) }}" target="_blank" style="color:#05018D; font-weight:600;">{{ $item->pedido_compra_nome }}</a>
                            <span style="color:#9ca3af;">— enviar outro arquivo substitui este</span> <button type="submit" form="rm-pedido" onclick="return confirm('Remover o pedido de compra anexado?')" style="background:none; border:none; color:#dc2626; font-size:12px; text-decoration:underline; cursor:pointer; padding:0; margin-left:6px;">Remover pedido de compra</button>
                        </div>
                    @endif
                    <input type="file" name="pedido_compra" accept=".pdf,.jpg,.jpeg,.png,.webp" style="font-size:13px;">
                    @error('pedido_compra') <div style="{{ $erroStyle }}">{{ $message }}</div> @enderror
                </div>

                <div style="margin-top:24px; display:flex; justify-content:flex-end;">
                    <button type="submit" class="m-botao" style="background:#05018D; color:#fff; border:none; padding:10px 22px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer;">
                        Salvar dados da compra
                    </button>
                </div>
            </form>

            @if($item->pedido_compra_path)
                <form id="rm-pedido" method="POST" action="{{ route('admin.compras.pedido.remover', $item) }}" style="display:none;">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </div>

        {{-- Resumo do andamento --}}
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; box-shadow:0 1px 4px rgba(0,0,0,0.06); font-size:13px; color:#374151;">
            <p style="margin:0 0 12px; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px;">Andamento</p>
            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f3f4f6;">
                <span style="color:#6b7280;">Solicitante</span><span>{{ $item->requester_name ?? $item->user?->name ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f3f4f6;">
                <span style="color:#6b7280;">Pedido em</span><span>{{ $item->created_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}</span>
            </div>
            @if($item->product_code)
                <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f3f4f6;">
                    <span style="color:#6b7280;">Código informado</span><span>{{ $item->product_code }}</span>
                </div>
            @endif
            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f3f4f6;">
                <span style="color:#6b7280;">Conferência</span>@include('admin.compras._conferencia')
            </div>
            <div style="display:flex; justify-content:space-between; padding:6px 0;">
                <span style="color:#6b7280;">Entrada</span>
                <span>{{ $item->entrada_concluida_em?->timezone('America/Sao_Paulo')->format('d/m/Y') ?? 'Ainda não' }}</span>
            </div>
            @if($item->admin_note)
                <p style="margin:12px 0 0; padding:10px; background:#f9fafb; border-radius:8px; color:#4b5563;">{{ $item->admin_note }}</p>
            @endif
        </div>
    </div>
</div>

@endsection
