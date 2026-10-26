{{-- Câmera ÚNICA da página. No celular, o campo de arquivo comum abre a galeria; o botão "Tirar foto"
     chama tirarFoto('id-do-campo'), que abre a câmera traseira e coloca a foto tirada nesse campo.
     Fica fora de qualquer form e sem name: só o campo de destino é enviado. --}}
<input type="file" id="camera-conferencia" accept="image/*" capture="environment" style="display:none;">

<script>
function tirarFoto(alvoId) {
    var camera = document.getElementById('camera-conferencia');
    camera.dataset.alvo = alvoId;
    camera.value = '';
    camera.click();
}

document.getElementById('camera-conferencia').addEventListener('change', function () {
    var foto = this.files[0];
    var alvo = document.getElementById(this.dataset.alvo);
    if (!foto || !alvo) return;
    try {
        var dt = new DataTransfer();
        // Campo de várias fotos: soma à seleção atual; campo de uma foto: substitui.
        if (alvo.multiple) {
            for (var i = 0; i < alvo.files.length; i++) dt.items.add(alvo.files[i]);
        }
        dt.items.add(foto);
        alvo.files = dt.files;
        alvo.dispatchEvent(new Event('change', { bubbles: true }));
    } catch (e) {
        alert('Este navegador não deixou usar a foto da câmera. Toque no campo de arquivo e escolha "Câmera".');
    }
});
</script>
