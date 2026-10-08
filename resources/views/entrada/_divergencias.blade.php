{{-- Aba "Divergências" da Entrada: itens que a conferência marcou como divergentes e que ainda aguardam decisão. --}}
{{-- Mesmo desenho das outras listagens: uma linha por requisição; ao abrir, cada item é um cartão. --}}

<div style="margin-bottom:14px; padding:10px 14px; background:#fff; border:1px solid #e5e7eb; border-left:4px solid #b8301a; border-radius:8px; color:#374151; font-size:13px; line-height:1.5;">
    Estes itens chegaram com divergência e <strong>ainda aguardam decisão</strong>. Se já foi liberado (por exemplo, o admin avisou no grupo interno),
    use <strong>Dar Entrada</strong> e escreva na observação o motivo. Quando o admin decide pelas Pendências, o item passa para a aba <strong>Aguardando</strong>.
</div>

@if($requests->isEmpty())
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; text-align:center; padding:48px 16px; color:#9ca3af; font-size:15px;">
        Nenhuma divergência em análise.
    </div>
@else
    <div class="lista-resp" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e5e7eb;">
                        @php $thDiv = 'padding:12px 16px; color:#6b7280; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap;'; @endphp
                        <th style="{{ $thDiv }} text-align:left;">Nº</th>
                        <th style="{{ $thDiv }} text-align:left;">Vendedor</th>
                        <th style="{{ $thDiv }} text-align:left;">Itens</th>
                        <th style="{{ $thDiv }} text-align:left;">Fornecedor</th>
                        <th style="{{ $thDiv }} text-align:left;">Data da compra</th>
                        <th style="{{ $thDiv }} text-align:right;">Pedido / Recebido</th>
                        <th style="{{ $thDiv }} text-align:left;">Entrega</th>
                        <th style="{{ $thDiv }} text-align:left;">Situação</th>
                        <th style="{{ $thDiv }} text-align:right;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $grupo)
                        @php
                            $primeiroDiv = $grupo->first();
                            $chaveDiv = $primeiroDiv->grupo_id;
                            $produtosResumoDiv = $grupo->pluck('product_name')->filter()->implode(', ');
                            if (mb_strlen($produtosResumoDiv) > 80) {
                                $produtosResumoDiv = mb_substr($produtosResumoDiv, 0, 80) . '…';
                            }
                            // O mesmo fornecedor escrito de formas diferentes conta como um só.
                            $fornecedoresDiv = $grupo->pluck('supplier')->filter()->unique(fn ($f) => \App\Support\RankingPorNome::chave($f));
                            $fornecedorDiv = $fornecedoresDiv->count() === 0 ? '—' : ($fornecedoresDiv->count() === 1 ? $fornecedoresDiv->first() : $fornecedoresDiv->count() . ' fornecedores');
                            $entregasDiv = $grupo->map(fn ($r) => $r->tipo_entrega === 'entrega_direta' ? 'Venda Casada' : 'Estoque')->unique();
                            $tdDiv = 'padding:12px 16px; font-size:14px; color:#374151; vertical-align:middle;';
                        @endphp
                        <tr class="grupo-cabecalho" style="border-bottom:1px solid #eef0f3; cursor:pointer;" onmouseover="this.style.background='#f7f8fa'" onmouseout="this.style.background='transparent'" onclick="toggleGrupoRequisicao('{{ $chaveDiv }}')">
                            <td class="lr-num" style="{{ $tdDiv }} font-weight:700; color:#111827; white-space:nowrap;">#{{ $primeiroDiv->id }}</td>
                            <td data-rotulo="Vendedor" style="{{ $tdDiv }}">{{ $primeiroDiv->requester_name ?? 'Não informado' }}</td>
                            <td class="lr-larga" data-rotulo="Itens" style="{{ $tdDiv }} max-width:380px;">
                                <div style="font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $produtosResumoDiv }}</div>
                                <div style="font-size:12px; color:#6b7280; margin-top:2px;">{{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }}</div>
                            </td>
                            @php $datasCompra = $grupo->pluck('data_compra')->filter()->map(fn ($d) => $d->format('d/m/Y'))->unique()->values(); $dataCompra = $datasCompra->count() === 0 ? '—' : ($datasCompra->count() === 1 ? $datasCompra->first() : $datasCompra->first() . ' +'); @endphp
                            <td data-rotulo="Fornecedor" style="{{ $tdDiv }}">{{ $fornecedorDiv }}</td>
                            <td data-rotulo="Data da compra" style="{{ $tdDiv }} white-space:nowrap;">{{ $dataCompra }}</td>
                            <td data-rotulo="Pedido / recebido" style="{{ $tdDiv }} text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums;">{{ $grupo->sum('quantity') }} / <strong style="color:#b8301a;">{{ $grupo->sum(fn ($r) => (int) $r->quantidade_recebida) }}</strong></td>
                            <td data-rotulo="Entrega" style="{{ $tdDiv }} font-size:13px; white-space:nowrap;">{{ $entregasDiv->count() === 1 ? $entregasDiv->first() : 'Mista' }}</td>
                            <td data-rotulo="Situação" style="{{ $tdDiv }}">
                                <span style="background:#fff; color:#b8301a; border:1px solid #b8301a; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Divergente</span>
                            </td>
                            <td class="lr-acao" style="{{ $tdDiv }} text-align:right;">
                                <button type="button" onclick="event.stopPropagation(); toggleGrupoRequisicao('{{ $chaveDiv }}')"
                                        style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:7px 16px; border-radius:9999px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                    <span id="seta-grupo-{{ $chaveDiv }}">Ver itens</span>
                                </button>
                            </td>
                        </tr>
                        @foreach($grupo as $req)
                        <tr class="grupo-item-{{ $chaveDiv }}" style="display:none; background:#f7f8fa;">
                            <td colspan="9" style="padding:{{ $loop->first ? '14px' : '0' }} 20px {{ $loop->last ? '18px' : '10px' }} 20px; {{ $loop->last ? 'border-bottom:1px solid #e5e7eb;' : '' }}">
                                <x-item-requisicao :req="$req">
                                    <span style="align-self:center; font-size:12.5px; color:#6b7280;">Pedido: <strong style="color:#111827;">{{ $req->quantity }}</strong> · Recebido: <strong style="color:#b8301a;">{{ $req->quantidade_recebida ?? '—' }}</strong></span>
                                    @if($req->conferente)
                                        <span style="align-self:center; font-size:12.5px; color:#6b7280;">Conferido por {{ $req->conferente->name }}</span>
                                    @endif
                                    <button type="button" onclick="document.getElementById('modal-entrada-div-{{ $req->id }}').style.display='flex'"
                                            style="background:#05018D; color:#fff; border:1px solid #05018D; border-radius:9999px; padding:7px 18px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                        Dar Entrada
                                    </button>
                                </x-item-requisicao>
                                <div style="margin-top:8px; font-size:12.5px; font-weight:600; color:#b45309;">
                                    ⏳ {{ $req->tipo_entrega === 'entrega_direta' ? 'Venda Casada: a conferência decide se avança.' : 'Aguardando decisão do admin (tela Pendências).' }}
                                </div>
                            </td>
                        </tr>

                        {{-- Janela Dar Entrada com divergência: mesmo modelo largo das outras janelas, com as notas ao lado. --}}
                        <div id="modal-entrada-div-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">
                            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:900px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow:hidden; text-align:left;">
                                <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc;">
                                    <div>
                                        <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Dar Entrada com divergência</h3>
                                        <div style="margin-top:4px; font-size:13px; color:#64748b; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                            <span style="font-weight:600; color:#1e293b;">{{ $req->product_name }}</span>
                                            <span>·</span>
                                            <span>Solicitante: <strong>{{ $req->requester_name }}</strong></span>
                                            <span>·</span>
                                            <span>Pedido / recebido: <strong>{{ $req->quantity }} / {{ $req->quantidade_recebida ?? '—' }}</strong></span>
                                            <span>·</span>
                                            <span>Compra em: <strong>{{ $req->data_compra ? $req->data_compra->format('d/m/Y') : '—' }}</strong></span>
                                        </div>
                                    </div>
                                    <button type="button" onclick="document.getElementById('modal-entrada-div-{{ $req->id }}').style.display='none'" aria-label="Fechar"
                                            style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">✕</button>
                                </div>

                                <form method="POST" action="{{ route('entrada.darEntrada', $req) }}" onsubmit="return protegerEnvioDuplo(this)" style="display:flex; flex-direction:column; flex:1; min-height:0; overflow:hidden; margin:0;">
                                    @csrf
                                    @method('PATCH')

                                    <div class="jan-corpo" style="padding:22px 24px; display:grid; grid-template-columns:1.2fr 1fr; gap:28px; flex:1; min-height:0; overflow-y:auto;">
                                        <div style="display:grid; grid-template-columns:1.6fr 1fr; column-gap:12px; align-content:start;">
                                            <div style="grid-column:1 / -1; font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Entrada</div>

                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Vendedor destino</label>
                                                <input type="text" name="vendedor_destino" value="{{ $req->requester_name }}" required style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                                            </div>
                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Quantidade dada entrada</label>
                                                <input type="number" name="quantidade_entrada" value="{{ $req->quantidade_recebida ?? $req->quantity }}" readonly style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background:#f1f5f9; color:#64748b; font-weight:700;">
                                            </div>
                                            <div style="grid-column:1 / -1; margin:-6px 0 14px; font-size:12px; color:#64748b;">A entrada é da quantidade que chegou ({{ $req->quantidade_recebida ?? $req->quantity }}). O que faltou não volta para a conferência por aqui.</div>

                                            <div style="grid-column:1 / -1;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Observação <span style="color:#ef4444;">*</span></label>
                                                <textarea name="obs_entrada" rows="3" maxlength="500" required placeholder="Ex: admin liberou no grupo interno em 05/10..." style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                                                <div style="margin-top:4px; font-size:12px; color:#64748b;">Obrigatória: explica por que o item entrou mesmo com divergência.</div>
                                            </div>
                                        </div>

                                        <div>
                                            <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Notas e ocorrências</div>
                                            <x-obs-todas :item="$req" margem="14px" :plano="true" />
                                            @if($req->pedido_compra_path)
                                                <div style="font-size:13px;">
                                                    <a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="color:#05018D; font-weight:600; text-decoration:underline;">📎 Pedido de compra{{ $req->pedido_compra_nome ? ': ' . $req->pedido_compra_nome : '' }}</a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div style="padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:10px;">
                                        <button type="button" onclick="document.getElementById('modal-entrada-div-{{ $req->id }}').style.display='none'"
                                                style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                                        <button type="submit"
                                                style="padding:8px 22px; border-radius:6px; background:#05018D; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer;">Confirmar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
            <div style="padding:16px 20px; border-top:1px solid #f3f4f6; display:flex; justify-content:center;">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
@endif
