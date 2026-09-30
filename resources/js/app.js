import './bootstrap';
import './push';

import Alpine from 'alpinejs';
import fornecedorInput from './fornecedor-input';

window.Alpine = Alpine;

Alpine.data('fornecedorInput', fornecedorInput);

Alpine.start();
