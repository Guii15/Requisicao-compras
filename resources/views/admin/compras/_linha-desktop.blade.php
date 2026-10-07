{{--
    Um item de Compras Feitas (desktop): o mesmo cartão do painel do admin (<x-item-requisicao>),
    com o botão que abre a janela "Dados da compra" na própria tela.
--}}
@php
    $temDados = $item->temDadosDaCompra();
    $travada = $item->status_conferencia !== null ? 'conferido' : (($item->quantidade_original !== null || $item->restante_de_id !== null) ? 'parcial' : '');
    $brl = fn ($v) => $v !== null ? number_format($v, 2, ',', '.') : '';
    $dadosJanela = [
        'id'          => $item->id,
        'produto'     => $item->product_name,
        'solicitante' => $item->requester_name ?? $item->user?->name ?? '—',
        'pedidoEm'    => $item->created_at?->timezone('America/Sao_Paulo')->format('d/m/Y'),
        'quantity'    => $item->quantity,
        'travada'     => $travada,
        'supplier'    => $item->supplier,
        'empresa'     => $item->empresa,
        'codigo'      => $item->codigo_fornecedor,
        'unitario'    => $brl($item->preco_unitario),
        'caixa'       => $brl($item->preco_caixa),
        'valor'       => $brl($item->valor),
        'dataCompra'  => $item->data_compra?->format('Y-m-d'),
        'condicao'    => $item->condicao_pagamento,
        'parcelas'    => $item->parcelas,
        'vencimento'  => $item->primeiro_vencimento?->format('Y-m-d'),
        'url'         => route('admin.compras.update', $item),
        'pedidoNome'  => $item->pedido_compra_path ? $item->pedido_compra_nome : null,
        'pedidoUrl'   => $item->pedido_compra_path ? route('admin.compras.pedido', $item) : null,
        'removerUrl'  => $item->pedido_compra_path ? route('admin.compras.pedido.remover', $item) : null,
        'coleta'      => $item->data_coleta?->format('d/m/Y'),
        'entrada'     => $item->entrada_concluida_em?->timezone('America/Sao_Paulo')->format('d/m/Y'),
        'nota'        => $item->admin_note,
        'temDados'    => $temDados,
    ];
    $rotuloConferencia = ['conferido_ok' => 'Conferido', 'avancado_mesmo_assim' => 'Conferido com divergência', 'divergente' => 'Divergente', 'cancelado' => 'Cancelado'][$item->status_conferencia] ?? 'Não conferido';
@endphp
<tr class="grupo-item-compra-{{ $chave }}" style="display:none; background:#f7f8fa;">
    <td colspan="9" style="padding:{{ $primeiro ? '14px' : '0' }} 20px {{ $ultimo ? '18px' : '10px' }} 20px; {{ $ultimo ? 'border-bottom:1px solid #e5e7eb;' : '' }}">
        <x-item-requisicao :req="$item">
            @unless($temDados)
                <span style="align-self:center; color:#b45309; font-size:12.5px; font-weight:600;">Falta registrar data e preço</span>
            @endunless
            <button type="button" data-compra-id="{{ $item->id }}" data-compra="{{ json_encode($dadosJanela) }}" data-conferencia="{{ $rotuloConferencia }}" onclick="abrirCompra(this)"
                    style="{{ $temDados
                        ? 'background:#fff; color:#374151; border:1px solid #d1d5db;'
                        : 'background:#05018D; color:#fff; border:1px solid #05018D;' }} border-radius:9999px; padding:7px 18px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                {{ $temDados ? 'Editar' : 'Registrar compra' }}
            </button>
        </x-item-requisicao>
    </td>
</tr>
