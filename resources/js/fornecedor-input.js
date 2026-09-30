export default function fornecedorInput({ texto, fornecedorId, url, parecidos }) {
    return {
        texto: texto || '',
        fornecedorId: fornecedorId || null,
        url,
        parecidos: parecidos || [],
        resultados: [],
        aberto: false,
        buscou: false,
        confirmarNovo: false,
        avisoForte: false,
        espera: null,

        init() {
            // Não deixa enviar um nome novo parecido com um existente sem a pessoa decidir.
            // Usa campo.form (e não closest): os modais do admin têm o <form> dentro de tabela,
            // e o navegador tira a tag do lugar, mas mantém os campos ligados ao formulário.
            this.$refs.campo.form?.addEventListener('submit', (evento) => {
                if (!this.fornecedorId && this.texto.trim() && this.parecidos.length && !this.confirmarNovo) {
                    evento.preventDefault();
                    evento.stopImmediatePropagation();
                    this.avisoForte = true;
                }
            }, true);
        },

        digitou() {
            this.fornecedorId = null;
            this.confirmarNovo = false;
            this.avisoForte = false;
            clearTimeout(this.espera);
            this.espera = setTimeout(() => this.buscar(), 250);
        },

        async buscar() {
            const termo = this.texto.trim();
            if (!termo) {
                this.resultados = [];
                this.parecidos = [];
                this.buscou = false;
                return;
            }
            try {
                const { data } = await window.axios.get(this.url, { params: { q: termo } });
                if (termo !== this.texto.trim()) return;
                this.resultados = data.resultados;
                this.parecidos = data.parecidos;
                if (data.exato) this.fornecedorId = data.exato.id;
                this.buscou = true;
                this.aberto = true;
            } catch (erro) {
                console.error('Busca de fornecedor:', erro);
            }
        },

        escolher(fornecedor) {
            this.texto = fornecedor.nome;
            this.fornecedorId = fornecedor.id;
            this.parecidos = [];
            this.resultados = [];
            this.aberto = false;
            this.confirmarNovo = false;
            this.avisoForte = false;
        },
    };
}
