@extends('layouts.app')

@section('content')

<div style="padding: 8px 0;">

    {{-- Cabeçalho --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Painel Administrativo</h1>
            <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Gerencie os usuários do sistema</p>
        </div>
    </div>

    {{-- Abas --}}
    <div style="display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid #e5e7eb;">
        <a href="{{ route('admin.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:transparent; color:#6b7280; border:2px solid transparent; border-bottom:2px solid transparent;"
           onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'">
            Requisições
        </a>
        <a href="{{ route('admin.users.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:#05018D; color:#fff; border:2px solid #05018D; border-bottom:2px solid #05018D;">
            Usuários
        </a>
        <a href="{{ route('pendencias.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:transparent; color:#6b7280; border:2px solid transparent; border-bottom:2px solid transparent;"
           onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'">
            📋 Pendências
        </a>
        <a href="{{ route('admin.historico-compras') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:transparent; color:#6b7280; border:2px solid transparent; border-bottom:2px solid transparent;"
           onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'">
            🗂️ Histórico de Compras
        </a>
    </div>

    {{-- Mensagens --}}
    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            ✕ {{ session('error') }}
        </div>
    @endif

    <div style="display:grid; grid-template-columns:1fr 360px; gap:20px; align-items:start;">

        {{-- Tabela de usuários --}}
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
            <div style="padding:16px 20px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:15px; font-weight:700; color:#111827;">Usuários cadastrados</span>
                <span style="background:#f3f4f6; color:#6b7280; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">{{ $users->count() }}</span>
            </div>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                        <th style="padding:11px 16px; text-align:left; color:#fff; font-size:12px; font-weight:600;">Nome</th>
                        <th style="padding:11px 16px; text-align:left; color:#fff; font-size:12px; font-weight:600;">E-mail</th>
                        <th style="padding:11px 16px; text-align:center; color:#fff; font-size:12px; font-weight:600;">Perfil</th>
                        <th style="padding:11px 16px; text-align:center; color:#fff; font-size:12px; font-weight:600;" colspan="2">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr style="border-bottom:1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">
                                {{ $u->name }}
                                @if($u->id === auth()->id())
                                    <span style="font-size:11px; color:#9ca3af; font-weight:400;">(você)</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $u->email }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($u->is_admin)
                                    <span style="background:#ede9fe; color:#7c3aed; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Admin</span>
                                @elseif($u->role === 'conferente')
                                    <span style="background:#dbeafe; color:#2563eb; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Conferente</span>
                                @elseif($u->role === 'entrada')
                                    <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Entrada</span>
                                @else
                                    <span style="background:#f3f4f6; color:#6b7280; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Vendedor</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; text-align:center;">
                                <div style="display:flex; gap:6px; justify-content:center;">
                                    <button onclick="document.getElementById('modal-senha-{{ $u->id }}').style.display='flex'"
                                            style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:6px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer;"
                                            onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                                        Senha
                                    </button>
                                    @if($u->id !== auth()->id())
                                        <button onclick="document.getElementById('modal-perfil-{{ $u->id }}').style.display='flex'"
                                                style="background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; border-radius:6px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer;"
                                                onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
                                            Perfil
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                          onsubmit="return confirm('Tem certeza que deseja remover {{ addslashes($u->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; border-radius:6px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer;"
                                                onmouseover="this.style.background='#fca5a5'" onmouseout="this.style.background='#fee2e2'">
                                            Remover
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size:12px; color:#d1d5db;">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- Modal redefinir senha --}}
                        <div id="modal-senha-{{ $u->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:400px; margin:16px;">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Redefinir Senha</h3>
                                <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $u->name }}</p>
                                <form method="POST" action="{{ route('admin.users.resetPassword', $u) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div style="margin-bottom:14px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Nova senha</label>
                                        <input type="password" name="password" placeholder="Mínimo 8 caracteres"
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                    </div>
                                    <div style="margin-bottom:20px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Confirmar senha</label>
                                        <input type="password" name="password_confirmation" placeholder="Repita a nova senha"
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                    </div>
                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <button type="button" onclick="document.getElementById('modal-senha-{{ $u->id }}').style.display='none'"
                                                style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#1d4ed8); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                            Salvar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Modal editar perfil --}}
                        @if($u->id !== auth()->id())
                        <div id="modal-perfil-{{ $u->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:400px; margin:16px;">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Editar Perfil</h3>
                                <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $u->name }}</p>
                                @php
                                    $perfilAtual = $u->is_admin ? 'admin' : ($u->role ?? 'vendedor');
                                @endphp
                                <form method="POST" action="{{ route('admin.users.updateRole', $u) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div style="margin-bottom:20px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Perfil</label>
                                        <select name="perfil" style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                            <option value="vendedor" {{ $perfilAtual === 'vendedor' ? 'selected' : '' }}>Vendedor</option>
                                            <option value="conferente" {{ $perfilAtual === 'conferente' ? 'selected' : '' }}>Conferente</option>
                                            <option value="entrada" {{ $perfilAtual === 'entrada' ? 'selected' : '' }}>Entrada</option>
                                            <option value="admin" {{ $perfilAtual === 'admin' ? 'selected' : '' }}>Admin</option>
                                        </select>
                                    </div>
                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <button type="button" onclick="document.getElementById('modal-perfil-{{ $u->id }}').style.display='none'"
                                                style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#1d4ed8); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                            Salvar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                    @empty
                        <tr>
                            <td colspan="4" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:14px;">
                                Nenhum usuário cadastrado
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Formulário novo usuário --}}
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:24px;">
            <h2 style="margin:0 0 20px; font-size:16px; font-weight:700; color:#111827;">Novo Usuário</h2>

            @if($errors->any())
                <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:12px 14px; border-radius:8px; margin-bottom:16px; font-size:13px;">
                    @foreach($errors->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Nome</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Nome completo"
                           style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:9px 12px; font-size:14px; box-sizing:border-box;">
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="email@empresa.com.br"
                           style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:9px 12px; font-size:14px; box-sizing:border-box;">
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Senha</label>
                    <input type="password" name="password" placeholder="Mínimo 8 caracteres"
                           style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:9px 12px; font-size:14px; box-sizing:border-box;">
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Confirmar senha</label>
                    <input type="password" name="password_confirmation" placeholder="Repita a senha"
                           style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:9px 12px; font-size:14px; box-sizing:border-box;">
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Perfil</label>
                    <select name="perfil" style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:9px 12px; font-size:14px; box-sizing:border-box;">
                        <option value="vendedor" {{ old('perfil', 'vendedor') === 'vendedor' ? 'selected' : '' }}>Vendedor</option>
                        <option value="conferente" {{ old('perfil') === 'conferente' ? 'selected' : '' }}>Conferente</option>
                        <option value="entrada" {{ old('perfil') === 'entrada' ? 'selected' : '' }}>Entrada</option>
                        <option value="admin" {{ old('perfil') === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>

                <button type="submit"
                        style="width:100%; background:linear-gradient(90deg,#05018D,#1d4ed8); color:#fff; border:none; border-radius:8px; padding:11px; font-size:14px; font-weight:700; cursor:pointer;"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    Criar Usuário
                </button>
            </form>
        </div>

    </div>

</div>

@endsection
