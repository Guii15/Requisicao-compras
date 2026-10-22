{{-- Janela "Registrar Coleta" ÚNICA da página: abrirModalColeta(id) lê o <template id="coleta-item-ID"> do item e preenche esta janela. --}}
<div id="modal-coleta" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:2000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:980px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); overflow:hidden;">
        <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; gap:12px; background:#f8fafc;">
            <div style="min-width:0;">
                <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Registrar Coleta</h3>
                <div style="margin-top:4px; font-size:13px; color:#64748b; display:flex; align-items:center; gap:8px; flex-wrap:wrap; min-width:0;">
                    <span id="coleta-produto" style="font-weight:600; color:#1e293b; overflow-wrap:anywhere;"></span>
                    <span>·</span>
                    <span>Solicitante: <strong id="coleta-solicitante"></strong></span>
                    <span>·</span>
                    <span>Qtd: <strong id="coleta-qtd"></strong></span>
                </div>
            </div>
            <button type="button" onclick="fecharModalColeta()" aria-label="Fechar"
                    style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">✕</button>
        </div>

        <form method="POST" action="" id="form-coleta" onsubmit="return validarColeta()" style="display:flex; flex-direction:column; flex:1; min-height:0; overflow:hidden; margin:0;">
            @csrf
            @method('PATCH')
            <div class="jan-corpo" style="padding:22px 24px; display:grid; grid-template-columns:1fr 1.1fr; gap:28px; flex:1; min-height:0; overflow-y:auto;">
                <div>
                    <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Coleta</div>
                    <div style="margin-bottom:16px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Data da coleta <span style="color:#dc2626;">*</span></label>
                        <input type="datetime-local" name="data_coleta" id="data-coleta" required
                               value="{{ now()->format('Y-m-d\TH:i') }}"
                               style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Marcar como <span style="color:#dc2626;">*</span></label>
                        <div style="display:flex; gap:18px; flex-wrap:wrap;">
                            <label style="display:inline-flex; align-items:center; gap:6px; font-size:13.5px; color:#334155; cursor:pointer;">
                                <input type="radio" name="status_coleta" value="coletado" checked> Coletado
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:6px; font-size:13.5px; color:#b8301a; cursor:pointer;">
                                <input type="radio" name="status_coleta" value="atraso"> Atraso
                            </label>
                        </div>
                    </div>
                </div>

                <div style="min-width:0;">
                    <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Notas e ocorrências</div>
                    <div id="coleta-notas"></div>
                </div>
            </div>

            <div style="padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="fecharModalColeta()"
                        style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit"
                        style="padding:8px 22px; border-radius:6px; background:#05018D; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer;">Confirmar Coleta</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalColeta(id) {
    var modelo = document.getElementById('coleta-item-' + id);
    if (!modelo) return;
    var d = modelo.dataset;
    var f = document.getElementById('form-coleta');
    f.reset(); // devolve a data ao valor de quando a página abriu e o rádio a "Coletado"
    f.action = d.url;
    document.getElementById('coleta-produto').textContent = d.produto;
    document.getElementById('coleta-solicitante').textContent = d.solicitante;
    document.getElementById('coleta-qtd').textContent = d.qtd;
    document.getElementById('coleta-notas').replaceChildren(modelo.content.cloneNode(true));
    document.getElementById('modal-coleta').style.display = 'flex';
}
function fecharModalColeta() {
    document.getElementById('modal-coleta').style.display = 'none';
}
function validarColeta() {
    var dataInput = document.getElementById('data-coleta');
    if (!dataInput.value) {
        alert('Por favor, selecione a data da coleta!');
        return false;
    }
    return true;
}
</script>
