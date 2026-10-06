{{--
    Cartão de um item da requisição: cabeçalho + 3 colunas (produto e anexos, compra, notas).
    O conteúdo do slot são os botões de ação do item (Editar, Exportar, Atualizar...).
    O slot "notas" (opcional) troca o conteúdo da coluna de notas; sem ele, mostra todas (<x-obs-todas>).
--}}
@props(['req'])

@php
    $temNotasItem = filled($req->admin_note) || filled($req->reason) || filled($req->justification)
        || filled($req->obs) || filled($req->observacao_conferencia) || filled($req->obs_entrada);
    $semAnexosItem = ! $req->product_url && ! $req->anexo_path && $req->fotosConferencia->isEmpty();
    $rotuloUrgenciaItem = ['alta' => 'Alta', 'media' => 'Média'][$req->urgency] ?? 'Baixa';
    $tituloColunaItem = 'margin:0 0 10px; line-height:1.4; font-size:11.5px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.6px;';
    $etiquetaItem = 'display:inline-block; background:#f3f4f6; color:#374151; padding:2px 10px; border-radius:9999px; font-size:11.5px; font-weight:600;';
    $etiquetaAlertaItem = 'display:inline-block; background:#fff; color:#b8301a; border:1px solid #b8301a; padding:1px 9px; border-radius:9999px; font-size:11.5px; font-weight:600;';
    $linkItem = 'display:block; font-size:13px; color:#05018D; text-decoration:underline; margin-top:6px; overflow-wrap:anywhere;';
@endphp

<div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; text-align:left;">

    {{-- Cabeçalho do item --}}
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px 20px; padding:14px 20px; border-bottom:1px solid #eef0f3;">
        <div style="min-width:0;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px;">
                <span style="font-size:16px; font-weight:700; color:#111827;">{{ $req->product_name }}</span>
                <x-status-requisicao :status="$req->status" estilo="contorno" />
            </div>
            <x-parcial-info :item="$req" />
            <div style="margin-top:3px; font-size:13px; color:#6b7280;">
                @if($req->product_code)Cód: {{ $req->product_code }} · @endif
                Quantidade: <strong style="color:#374151;">{{ $req->quantity }}</strong> ·
                Urgência: <strong style="color:{{ $req->urgency === 'alta' ? '#b8301a' : '#374151' }};">{{ $rotuloUrgenciaItem }}</strong>
            </div>
        </div>
        @if($slot->isNotEmpty())
            <div style="display:flex; flex-wrap:wrap; gap:8px;">{{ $slot }}</div>
        @endif
    </div>

    <div style="display:grid; grid-template-columns:repeat(3, minmax(0, 1fr));">

        {{-- Coluna 1: produto e anexos --}}
        <div style="padding:16px 20px; font-size:13px; color:#374151;">
            <p style="{{ $tituloColunaItem }}">Produto e anexos</p>
            <div>
                @if($req->entrada_concluida_em)
                    <span style="{{ $etiquetaItem }}">Entrada Realizada</span>
                    <span style="display:block; margin-top:2px; font-size:11.5px; color:#6b7280;">{{ $req->entrada_concluida_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</span>
                @elseif($req->status_conferencia === 'conferido_ok')
                    <span style="{{ $etiquetaItem }}">Conferido ✓ OK</span>
                @elseif($req->status_conferencia === 'divergente')
                    <span style="{{ $etiquetaAlertaItem }}">Conferido — Divergente</span>
                @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                    <span style="{{ $etiquetaItem }}">Conferido — Avançado Mesmo Assim</span>
                @elseif($req->status_conferencia === 'cancelado')
                    <span style="{{ $etiquetaAlertaItem }}">Cancelado</span>
                @elseif($req->status_conferencia === 'legado')
                @elseif($req->status === 'aprovado')
                    <span style="{{ $etiquetaItem }}">Aguardando conferência</span>
                @endif
            </div>
            @if($req->product_url)
                <a href="{{ $req->product_url }}" target="_blank" style="{{ $linkItem }}">Ver link</a>
            @endif
            @if($req->anexo_path)
                <a href="{{ route('requests.anexo', $req) }}" target="_blank" style="{{ $linkItem }}">📎 {{ $req->anexo_nome }}</a>
            @endif
            @if($req->fotosConferencia->isNotEmpty())
                {{-- A janela vai junto com o cartão: antes ela só existia na tela do vendedor e no admin o clique não fazia nada. --}}
                <a href="javascript:void(0)" onclick="document.getElementById('foto-item-{{ $req->id }}').style.display='flex'" title="Ver foto da conferência" style="display:inline-block; margin-top:8px;">
                    <img src="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" alt="Foto da conferência"
                         style="width:56px; height:56px; object-fit:cover; border-radius:8px; border:1px solid #e5e7eb; display:block;">
                    @if($req->fotosConferencia->count() > 1)
                        <span style="display:block; margin-top:3px; font-size:11.5px; color:#6b7280;">{{ $req->fotosConferencia->count() }} fotos</span>
                    @endif
                </a>
                <section id="foto-item-{{ $req->id }}" onclick="if(event.target===this)this.style.display='none'"
                         style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center;">
                    <div style="background:#fff; border-radius:12px; padding:24px; width:100%; max-width:480px; max-height:calc(100vh - 32px); overflow-y:auto; margin:16px; box-sizing:border-box;">
                        <h3 style="margin:0 0 4px; font-size:16px; font-weight:700; color:#111827;">Fotos da Conferência</h3>
                        <p style="margin:0 0 16px; font-size:12px; color:#6b7280;">{{ $req->product_name }}</p>
                        @foreach($req->fotosConferencia as $fotoItem)
                            <img src="{{ Storage::url($fotoItem->caminho_arquivo) }}" alt="Foto da conferência"
                                 style="width:100%; border-radius:8px; margin-bottom:12px; display:block;">
                        @endforeach
                        <div style="text-align:right;">
                            <button type="button" onclick="document.getElementById('foto-item-{{ $req->id }}').style.display='none'"
                                    style="padding:9px 24px; border-radius:9999px; border:1px solid #d1d5db; background:#fff; color:#374151; font-size:14px; font-weight:600; cursor:pointer;">
                                Fechar
                            </button>
                        </div>
                    </div>
                </section>
            @endif
            @if($semAnexosItem)
                <div style="margin-top:6px; color:#6b7280;">Nenhum anexo.</div>
            @endif
        </div>

        {{-- Coluna 2: compra --}}
        <div style="padding:16px 20px; border-left:1px solid #eef0f3; font-size:13px; color:#374151; line-height:1.8;">
            <p style="{{ $tituloColunaItem }}">Compra</p>
            @if($req->supplier)
                <div>Fornecedor: <strong style="color:#111827;">{{ $req->supplier }}</strong></div>
            @endif
            @if($req->temDadosDaCompra())
                <div>Unitário: R$ {{ number_format($req->preco_unitario, 2, ',', '.') }}</div>
                @if($req->preco_caixa)
                    <div>Caixa: R$ {{ number_format($req->preco_caixa, 2, ',', '.') }}</div>
                @endif
                @if($req->valor)
                    <div>Total: <strong style="color:#111827;">R$ {{ number_format($req->valor, 2, ',', '.') }}</strong></div>
                @endif
                @if($req->data_compra)
                    <div>Compra: {{ $req->data_compra->format('d/m/Y') }}</div>
                @endif
                @if($req->data_coleta)
                    <div>Coleta: {{ $req->data_coleta->format('d/m/Y') }}{{ $req->coletado_por ? ' (' . $req->coletado_por . ')' : '' }}</div>
                @endif
                @if($req->pedido_compra_path)
                    <div><a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="color:#05018D; text-decoration:underline;">📎 Pedido de compra</a></div>
                @endif
            @elseif($req->status === 'aprovado')
                <div style="color:#6b7280;">Ainda sem dados da compra.</div>
            @elseif($req->status === 'rejeitado')
                <div style="color:#6b7280;">Item rejeitado, sem compra.</div>
            @else
                <div style="color:#6b7280;">Os dados da compra entram depois da aprovação.</div>
            @endif
            @if($req->status === 'aprovado')
                <div>
                    Coleta atrasada:
                    @if($req->status_coleta === 'atraso')
                        <span style="background:#b8301a; color:#fff; padding:2px 10px; border-radius:9999px; font-size:12px; font-weight:700;">Sim</span>
                    @else
                        <span style="color:#6b7280;">Não</span>
                    @endif
                </div>
            @endif
        </div>

        {{-- Coluna 3: notas --}}
        <div style="padding:16px 20px; border-left:1px solid #eef0f3; font-size:13px; color:#374151;">
            <p style="{{ $tituloColunaItem }}">Notas e ocorrências</p>
            @isset($notas)
                {{ $notas }}
            @elseif($temNotasItem)
                <x-obs-todas :item="$req" margem="0" :plano="true" />
            @else
                <div style="color:#6b7280;">Nenhuma nota.</div>
            @endif
        </div>

    </div>
</div>
