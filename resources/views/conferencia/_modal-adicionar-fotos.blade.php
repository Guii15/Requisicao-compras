{{-- Janela "Adicionar fotos" ÚNICA da página: o botão do item conferido chama abrirAdicionarFotos(this) e passa os dados por data-*. --}}
<div id="modal-adicionar-fotos" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; padding:24px; width:100%; max-width:420px; margin:16px; text-align:left;">
        <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Adicionar fotos</h3>
        <p id="fotos-resumo" style="margin:0 0 6px; font-size:13px; color:#6b7280;"></p>
        <p style="margin:0 0 16px; font-size:12px; color:#9ca3af;">Ex.: o restante que chegou depois. As fotos ficam junto com as da conferência, com a data e a hora do envio.</p>

        <form method="POST" action="" enctype="multipart/form-data" id="form-adicionar-fotos" onsubmit="return protegerEnvioDuplo(this)">
            @csrf
            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Fotos (até 6)</label>
            <input type="file" name="fotos[]" accept=".jpg,.jpeg,.png,.webp" multiple required
                   style="width:100%; font-size:12px; color:#475569; margin-bottom:16px;">
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-adicionar-fotos').style.display='none'"
                        style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#374151; font-size:14px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit"
                        style="padding:9px 24px; border-radius:8px; background:#05018D; color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">Enviar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirAdicionarFotos(botao) {
    var f = document.getElementById('form-adicionar-fotos');
    f.reset();
    delete f.dataset.enviando;
    f.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = false; });
    f.action = botao.dataset.url;
    document.getElementById('fotos-resumo').textContent = botao.dataset.produto;
    document.getElementById('modal-adicionar-fotos').style.display = 'flex';
}
</script>
