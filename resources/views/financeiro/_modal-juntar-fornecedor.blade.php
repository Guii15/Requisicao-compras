{{-- Quadro "Juntar com outro fornecedor": quando o mesmo fornecedor foi digitado com nomes diferentes ("Joyce" e "Joyce Informática"). --}}
@php
    $campo = 'width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background-color:#fff;';
    $rotulo = 'display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;';
    [$parecidos, $demais] = $outros->partition(fn ($f) => $f['parecido']);
@endphp
<div id="modal-juntar" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:560px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow:hidden; text-align:left;">
        <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc;">
            <div>
                <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Juntar com outro fornecedor</h3>
                <div style="margin-top:4px; font-size:13px; color:#64748b;">Quando <strong style="color:#1e293b;">{{ $nome }}</strong> foi digitado com outro nome</div>
            </div>
            <button type="button" onclick="document.getElementById('modal-juntar').style.display='none'" aria-label="Fechar"
                    style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">✕</button>
        </div>
        <div style="padding:20px 24px; overflow-y:auto;">
            <form method="POST" action="{{ route('financeiro.juntarFornecedor', $chave) }}"
                  onsubmit="return confirm('Juntar os dois fornecedores? As compras e pagamentos passam a ficar num fornecedor só.') && protegerEnvioDuplo(this)">
                @csrf
                <div style="margin-bottom:14px;">
                    <label style="{{ $rotulo }}">É o mesmo fornecedor que <span style="color:#ef4444;">*</span></label>
                    <select name="outro" required onchange="juntarEscolhido(this)" style="{{ $campo }}">
                        <option value="">Escolha...</option>
                        @if($parecidos->isNotEmpty())
                            <optgroup label="Parecidos">
                                @foreach($parecidos as $f)<option value="{{ $f['chave'] }}" data-nome="{{ $f['nome'] }}">{{ $f['nome'] }} ({{ $f['compras'] }} {{ $f['compras'] === 1 ? 'compra' : 'compras' }})</option>@endforeach
                            </optgroup>
                        @endif
                        <optgroup label="Outros">
                            @foreach($demais as $f)<option value="{{ $f['chave'] }}" data-nome="{{ $f['nome'] }}">{{ $f['nome'] }} ({{ $f['compras'] }} {{ $f['compras'] === 1 ? 'compra' : 'compras' }})</option>@endforeach
                        </optgroup>
                    </select>
                </div>

                <div style="margin-bottom:14px;">
                    <span style="{{ $rotulo }}">Qual nome fica?</span>
                    <label style="display:flex; gap:8px; align-items:center; font-size:13.5px; color:#0f172a; margin-bottom:6px;">
                        <input type="radio" name="fica" value="outro" checked> <span id="juntar-nome-outro">O do outro fornecedor</span>
                    </label>
                    <label style="display:flex; gap:8px; align-items:center; font-size:13.5px; color:#0f172a;">
                        <input type="radio" name="fica" value="este"> {{ $nome }}
                    </label>
                </div>

                <p style="margin:0 0 18px; font-size:12.5px; color:#64748b;">As compras dos dois passam para o nome escolhido (o que foi digitado fica guardado). Quem digitar o outro nome daqui para frente cai no mesmo fornecedor.</p>

                <div style="margin:4px -24px -20px; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('modal-juntar').style.display='none'"
                            style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                    <button type="submit"
                            style="padding:8px 22px; border-radius:6px; background:#05018D; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer;">Juntar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function juntarEscolhido(select) {
    var opcao = select.options[select.selectedIndex];
    document.getElementById('juntar-nome-outro').textContent = opcao && opcao.dataset.nome ? opcao.dataset.nome : 'O do outro fornecedor';
}
</script>
