<x-guest-layout>
    @php
    $opcoes = [
        'vendedor'    => ['label' => 'Vendedor',    'desc' => 'Criar e acompanhar requisições', 'icone' => 'M6 8h12l1 12H5L6 8z M9 8V7a3 3 0 016 0v1'],
        'conferencia' => ['label' => 'Conferência', 'desc' => 'Conferir itens recebidos',       'icone' => 'M9 4h6v3H9V4z M9 5H7a1 1 0 00-1 1v13a1 1 0 001 1h10a1 1 0 001-1V6a1 1 0 00-1-1h-2 M9 13.5l2 2 4-4'],
        'entrada'     => ['label' => 'Entrada',     'desc' => 'Registrar entrada de mercadoria','icone' => 'M4 8.5l8-4 8 4v7.5l-8 4-8-4V8.5z M4 8.5l8 4 8-4 M12 12.5V20'],
        'financeiro'  => ['label' => 'Financeiro',  'desc' => 'Contas a pagar por fornecedor',  'icone' => 'M3 7h18v10H3V7z M12 14.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z M6.5 10.5v3 M17.5 10.5v3'],
        'rma'         => ['label' => 'RMA',         'desc' => 'Consultar compras e fotos',      'icone' => 'M4 7h16v12H4V7z M9 7V5h6v2 M12 11v4 M10 13h4'],
        'admin'       => ['label' => 'Admin',      'desc' => 'Painel administrativo',          'icone' => 'M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z M9 12l2 2 4-4'],
    ];
    @endphp
    <style>.auth-card { max-width: 480px; }</style>

    <h2 style="font-size:22px; font-weight:800; color:#05018D; margin:0 0 4px;">Bem-vindo!</h2>
    <p style="font-size:13px; color:#9ca3af; margin:0 0 28px;">Selecione seu perfil para entrar</p>

    <div style="display:flex; flex-direction:column; gap:10px;">
        @foreach($opcoes as $chave => $opcao)
            <a href="{{ route('login.perfil', $chave) }}"
               style="display:flex; align-items:center; gap:14px; padding:14px 16px; border:1.5px solid #e5e7eb; border-radius:10px; text-decoration:none; transition:border-color 0.15s, background 0.15s;"
               onmouseover="this.style.borderColor='#05018D'; this.style.background='#f8f8ff'"
               onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='#fff'" >
                <span style="flex:none; display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; border-radius:50%; background:#eeeefa; color:#05018D;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $opcao['icone'] }}"/></svg>
                </span>
                <span style="flex:1; min-width:0;">
                    <span style="display:block; font-size:15px; font-weight:700; color:#05018D;">{{ $opcao['label'] }}</span>
                    <span style="display:block; font-size:12.5px; color:#9ca3af; margin-top:2px;">{{ $opcao['desc'] }}</span>
                </span>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
            </a>
        @endforeach
    </div>

</x-guest-layout>
