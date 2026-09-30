import './bootstrap';
import './push';

import Alpine from 'alpinejs';
import fornecedorInput from './fornecedor-input';

window.Alpine = Alpine;

Alpine.data('fornecedorInput', fornecedorInput);

// No celular, campos com data-placeholder-mobile usam um texto mais curto (o longo aparecia cortado).
if (window.matchMedia('(max-width: 767px)').matches) {
    document.querySelectorAll('[data-placeholder-mobile]').forEach((campo) => {
        campo.placeholder = campo.dataset.placeholderMobile;
    });
}

Alpine.start();
