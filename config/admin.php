<?php

return [
    /*
     * Unico e-mail com acesso a aba de Usuarios (cadastrar/remover admins, vendedores etc).
     * Os demais admins (compradores) nao veem nem acessam essa tela.
     */
    'super_admin_email' => env('SUPER_ADMIN_EMAIL'),
];
