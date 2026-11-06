{{-- Janela "Enviar ao RMA" ÚNICA da página: o botão do item chama abrirEnviarRma(this) e passa os dados por data-*.
     Precisa da câmera (conferencia._camera) incluída na página para o botão "Tirar foto". --}}
<div id="modal-enviar-rma" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; padding:24px; width:100%; max-width:440px; margin:16px; text-align:left;">
        <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#b8301a;">Enviar ao RMA</h3>
        <p id="rma-resumo" style="margin:0 0 6px; font-size:13px; color:#374151; font-weight:600;"></p>
        <p style="margin:0 0 16px; font-size:12px; color:#9ca3af;">O item com defeito vai para a aba do RMA, que preenche os dados para devolver ao fornecedor.</p>

        <form method="POST" action="" enctype="multipart/form-data" id="form-enviar-rma" onsubmit="return protegerEnvioDuplo(this)">
            @csrf
            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quantidade com defeito</label>
            <input type="number" name="quantidade" id="rma-quantidade" min="1" value="1" required
                   style="width:120px; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:14px; margin-bottom:14px;">

            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Qual é o defeito?</label>
            <textarea name="defeito" rows="3" required maxlength="2000" placeholder="Ex.: não liga, tela riscada, veio faltando peça"
                      style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:14px; box-sizing:border-box; margin-bottom:14px;"></textarea>

            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Foto do defeito (opcional)</label>
            <input type="file" name="foto" id="campo-foto-rma" accept="image/*" style="width:100%; font-size:12px; color:#475569;">
            <button type="button" onclick="tirarFoto('campo-foto-rma')" style="margin:6px 0 16px; padding:7px 14px; border-radius:6px; border:1.5px solid #05018D; background:#fff; color:#05018D; font-size:13px; font-weight:600; cursor:pointer;">📷 Tirar foto</button>

            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-enviar-rma').style.display='none'"
                        style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#374151; font-size:14px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit"
                        style="padding:9px 24px; border-radius:8px; background:#b8301a; color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">Enviar ao RMA</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirEnviarRma(botao) {
    var f = document.getElementById('form-enviar-rma');
    f.reset();
    delete f.dataset.enviando;
    f.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = false; });
    f.action = botao.dataset.url;
    var qtd = document.getElementById('rma-quantidade');
    qtd.max = botao.dataset.max;
    document.getElementById('rma-resumo').textContent = botao.dataset.produto + ' (até ' + botao.dataset.max + ' un.)';
    document.getElementById('modal-enviar-rma').style.display = 'flex';
}
</script>
