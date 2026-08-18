<x-guest-layout>
    @php
    $opcoes = [
        'vendedor'    => ['label' => 'Vendedor',    'desc' => 'Criar e acompanhar requisições'],
        'conferencia' => ['label' => 'Conferência', 'desc' => 'Conferir itens recebidos'],
        'entrada'     => ['label' => 'Entrada',     'desc' => 'Registrar entrada de mercadoria'],
        'admin'       => ['label' => 'Admin',       'desc' => 'Painel administrativo'],
    ];
    @endphp

    <h2 style="font-size:22px; font-weight:800; color:#05018D; margin:0 0 4px;">Bem-vindo!</h2>
    <p style="font-size:13px; color:#9ca3af; margin:0 0 28px;">Selecione seu perfil para entrar</p>

    <div style="display:flex; flex-direction:column; gap:10px;">
        @foreach($opcoes as $chave => $opcao)
            <a href="{{ route('login.perfil', $chave) }}"
               style="display:block; padding:14px 16px; border:1.5px solid #e5e7eb; border-radius:10px; text-decoration:none; transition:border-color 0.15s, background 0.15s;"
               onmouseover="this.style.borderColor='#05018D'; this.style.background='#f8f8ff'"
               onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='transparent'">
                <div style="font-size:15px; font-weight:700; color:#05018D;">{{ $opcao['label'] }}</div>
                <div style="font-size:12.5px; color:#9ca3af; margin-top:2px;">{{ $opcao['desc'] }}</div>
            </a>
        @endforeach
    </div>
</x-guest-layout>
