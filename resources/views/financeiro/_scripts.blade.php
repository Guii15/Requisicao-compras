<script>
function meioPagamento(id, meio) {
    document.getElementById('campo-banco-' + id).style.display = meio === 'dinheiro' ? 'none' : 'block';
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
