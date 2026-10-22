<script>
// Formas que saem de um banco (as outras, como dinheiro e as cadastradas, não exigem banco).
var MEIOS_COM_BANCO = @json(\App\Models\PagamentoCompra::MEIOS_COM_BANCO);

function meioPagamento(id, meio) {
    var novo = document.getElementById('meio-novo-' + id);
    novo.style.display = meio === '__novo' ? 'block' : 'none';
    novo.required = meio === '__novo';
    if (meio === '__novo') {
        novo.focus();
    }
    document.getElementById('campo-banco-' + id).style.display = meio === 'dinheiro' ? 'none' : 'block';
    document.getElementById('banco-obrig-' + id).style.display = MEIOS_COM_BANCO.indexOf(meio) >= 0 ? 'inline' : 'none';
    document.getElementById('banco-novo-' + id).required = meio !== 'dinheiro' && document.querySelector('#campo-banco-' + id + ' select').value === '__novo';

    var cheque = document.getElementById('campo-cheque-' + id);
    if (cheque) {
        cheque.style.display = meio === 'cheque' ? 'block' : 'none';
        cheque.querySelector('[name="cheque_primeira_folha"]').required = meio === 'cheque';
        previaCheque(id);
    }
}

// Mesma conta do servidor (DesdobramentoCheque): folhas iguais, cada uma abaixo do limite, em centavos.
var LIMITE_CHEQUE_CENTAVOS = {{ (int) round(config('financeiro.limite_cheque', 5000) * 100) }};

function folhasCheque(centavos) {
    if (centavos <= 0) {
        return [];
    }
    var maximo = LIMITE_CHEQUE_CENTAVOS - 1;
    var n = Math.ceil(centavos / maximo);
    var base = Math.floor(centavos / n), resto = centavos % n, folhas = [];
    for (var i = 0; i < n; i++) {
        folhas.push(base + (i < resto ? 1 : 0));
    }
    return folhas;
}

function reais(centavos) {
    return 'R$ ' + (centavos / 100).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function previaCheque(id) {
    var caixa = document.getElementById('campo-cheque-' + id);
    var previa = document.getElementById('previa-cheque-' + id);
    if (!caixa || !previa || caixa.style.display === 'none') {
        return;
    }
    var form = caixa.closest('form');
    // Sem a escolha à vista/parcelado (Pagar fornecedor), o valor é sempre o digitado.
    var forma = form.querySelector('[name="forma"]:checked');
    var parcelado = !forma || forma.value === 'parcelado';
    var texto = parcelado ? form.querySelector('[name="valor"]').value : caixa.dataset.aberto;
    if (parcelado) {
        texto = texto.replace(/[R$\s]/g, '');
        if (texto.indexOf(',') >= 0) {
            texto = texto.replace(/\./g, '').replace(',', '.');
        }
    }
    // Só aceita um valor bem escrito (12.500,00 ou 12500,00 ou 12500.00); texto colado errado não vira prévia enganosa.
    var valido = !parcelado || /^\d+(\.\d{1,2})?$/.test(texto);
    var centavos = valido ? Math.round(parseFloat(texto) * 100) : 0;
    if (!centavos || centavos <= 0) {
        previa.innerHTML = valido ? 'Informe o valor para ver as folhas.' : '<span style="color:#b91c1c; font-weight:600;">Valor inválido. Confira o "Valor pago".</span>';
        return;
    }
    var folhas = folhasCheque(centavos);
    var primeira = parseInt(form.querySelector('[name="cheque_primeira_folha"]').value.replace(/\D/g, ''), 10);
    var linhas = folhas.map(function (v, i) {
        var numero = isNaN(primeira) ? '?' : String(primeira + i).padStart(6, '0');
        return 'nº ' + numero + ' · ' + reais(v);
    });
    previa.innerHTML = '<strong>' + folhas.length + (folhas.length === 1 ? ' folha' : ' folhas') + '</strong> (cada uma abaixo de ' + reais(LIMITE_CHEQUE_CENTAVOS) + '):<br>' + linhas.join('<br>');
}

function bancoEscolhido(id, valor) {
    var campo = document.getElementById('banco-novo-' + id);
    campo.style.display = valor === '__novo' ? 'block' : 'none';
    campo.required = valor === '__novo';
    if (valor === '__novo') {
        campo.focus();
    }
}

function formaPagamento(id, forma) {
    document.getElementById('campo-valor-' + id).style.display = forma === 'parcelado' ? 'block' : 'none';
}

// Prévia do "Pagar fornecedor": quais compras o valor quita, da mais antiga para a mais nova.
function previaFornecedor() {
    var previa = document.getElementById('previa-fornecedor');
    if (!previa || typeof ORDEM_PAGAMENTO_FORNECEDOR === 'undefined') {
        return;
    }
    var texto = document.querySelector('#modal-pagar-forn [name="valor"]').value.replace(/[R$\s]/g, '');
    if (texto.indexOf(',') >= 0) {
        texto = texto.replace(/\./g, '').replace(',', '.');
    }
    var centavos = /^\d+(\.\d{1,2})?$/.test(texto) ? Math.round(parseFloat(texto) * 100) : 0;
    var saldo = ORDEM_PAGAMENTO_FORNECEDOR.reduce(function (t, c) { return t + c.centavos; }, 0);
    if (!centavos) {
        previa.innerHTML = '<span style="color:#b91c1c; font-weight:600;">Informe um valor válido (ex.: 12.500,00).</span>';
    } else if (centavos > saldo) {
        previa.innerHTML = '<span style="color:#b91c1c; font-weight:600;">O valor passa do saldo devedor (' + reais(saldo) + ').</span>';
    } else {
        var resta = centavos, linhas = [];
        ORDEM_PAGAMENTO_FORNECEDOR.forEach(function (c) {
            if (resta <= 0) {
                return;
            }
            var usa = Math.min(resta, c.centavos);
            resta -= usa;
            var quita = usa === c.centavos;
            linhas.push('<div style="display:flex; justify-content:space-between; gap:8px; padding:2px 0;"><span>#' + c.id + ' · ' + c.produto.replace(/</g, '&lt;')
                + (c.vence ? ' <span style="color:#9ca3af;">vence ' + c.vence + '</span>' : '') + '</span>'
                + '<span style="white-space:nowrap;"><strong>' + reais(usa) + '</strong> ' + (quita ? '<span style="color:#17794a;">quita</span>' : '<span style="color:#7a4f00;">parcial (falta ' + reais(c.centavos - usa) + ')</span>') + '</span></div>');
        });
        previa.innerHTML = '<div style="font-weight:600; margin-bottom:4px;">Vai quitar ' + linhas.length + (linhas.length === 1 ? ' compra' : ' compras') + ':</div>' + linhas.join('');
    }
    previaCheque('forn');
}

// Janela de pagamento que voltou com erro: reaplica o que depende da forma escolhida (cheque, banco novo...).
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[id^="modal-pagar-"][data-reabrir="1"]').forEach(function (modal) {
        var id = modal.id.replace('modal-pagar-', '');
        var meio = modal.querySelector('select[name="meio"]');
        if (meio) {
            meioPagamento(id, meio.value);
        }
        if (id === 'forn') {
            previaFornecedor();
        }
    });
});

function protegerEnvioDuplo(form) {
    if (form.dataset.enviando === '1') {
        return false;
    }
    form.dataset.enviando = '1';
    form.querySelectorAll('button[type="submit"]').forEach(function (botao) {
        botao.disabled = true;
    });
    return true;
}
</script>
