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
