<script>
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
