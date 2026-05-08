@extends('layouts.app')

@section('content')

<style>
.admin-input {
    width: 100%; background: var(--bg-input); border: 1px solid var(--border);
    border-radius: 8px; padding: 10px 12px; font-size: 14px; color: var(--text);
    font-family: inherit; outline: none; transition: border-color 0.2s; box-sizing: border-box;
}
.admin-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0,113,227,0.15); }
.admin-input::placeholder { color: var(--text3); }
.admin-label {
    display: block; font-size: 11px; font-weight: 700; color: var(--text3);
    margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.6px;
}
.users-table { width: 100%; border-collapse: collapse; }
.users-table th { padding: 11px 16px; color: #fff; font-size: 11px; font-weight: 700; text-align: left; white-space: nowrap; text-transform: uppercase; letter-spacing: 0.4px; }
.users-table td { padding: 14px 16px; font-size: 14px; color: var(--text2); border-top: 1px solid var(--border); vertical-align: middle; }
.users-table tbody tr:hover td { background: var(--bg-card); color: var(--text); }
.u-btn { padding: 5px 12px; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; border: none; font-family: inherit; text-decoration: none; display: inline-block; }
</style>

{{-- Header --}}
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <div>
        <h1 style="margin:0 0 4px; font-size:26px; font-weight:700; color:var(--text); letter-spacing:-0.5px;">Usuários</h1>
        <p style="margin:0; font-size:14px; color:var(--text2);">Gerencie os usuários e permissões do sistema</p>
    </div>
    <a href="{{ route('admin.index') }}"
       style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:var(--bg-card); border:1px solid var(--border); border-radius:9px; color:var(--text2); text-decoration:none; font-size:14px; font-weight:500;">
        ← Voltar ao painel
    </a>
</div>

{{-- Flash --}}
@if(session('success'))
    <div style="background:var(--success-bg); color:var(--success-text); border:1px solid currentColor; border-radius:10px; padding:12px 16px; margin-bottom:20px; font-size:14px; font-weight:500; display:flex; align-items:center; gap:8px;">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div style="background:var(--danger-bg); color:var(--danger-text); border:1px solid currentColor; border-radius:10px; padding:12px 16px; margin-bottom:20px; font-size:14px; font-weight:500;">
        ✕ {{ session('error') }}
    </div>
@endif

<div style="display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start;">

    {{-- Tabela de usuários --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:12px; overflow:hidden;">
        <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:15px; font-weight:700; color:var(--text);">Usuários cadastrados</span>
            <span style="background:var(--bg-input); color:var(--text2); padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; border:1px solid var(--border);">{{ $users->count() }}</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="users-table">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,var(--accent));">
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th style="text-align:center;">Perfil</th>
                        <th style="text-align:center;" colspan="2">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td style="font-weight:600; color:var(--text);">
                                {{ $u->name }}
                                @if($u->id === auth()->id())
                                    <span style="font-size:11px; color:var(--text3); font-weight:400;">(você)</span>
                                @endif
                            </td>
                            <td>{{ $u->email }}</td>
                            <td style="text-align:center;">
                                @if($u->is_admin)
                                    <span style="background:rgba(175,82,222,0.15); color:#c084fc; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Admin</span>
                                @else
                                    <span style="background:var(--bg-input); color:var(--text2); padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; border:1px solid var(--border);">Usuário</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                <button onclick="document.getElementById('modal-senha-{{ $u->id }}').style.display='flex'"
                                        class="u-btn" style="background:rgba(0,113,227,0.12); color:var(--accent);">
                                    Senha
                                </button>
                            </td>
                            <td style="text-align:center;">
                                @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                          onsubmit="return confirm('Tem certeza que deseja remover {{ addslashes($u->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="u-btn" style="background:var(--danger-bg); color:var(--danger-text);">
                                            Remover
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size:12px; color:var(--text3);">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- Modal redefinir senha --}}
                        <div id="modal-senha-{{ $u->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:var(--bg2); border:1px solid var(--border); border-radius:16px; padding:28px; width:100%; max-width:400px; margin:16px; box-shadow:0 24px 48px rgba(0,0,0,0.4);">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:var(--text);">Redefinir Senha</h3>
                                <p style="margin:0 0 20px; font-size:13px; color:var(--text3);">{{ $u->name }}</p>
                                <form method="POST" action="{{ route('admin.users.resetPassword', $u) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div style="margin-bottom:14px;">
                                        <label class="admin-label">Nova senha</label>
                                        <input type="password" name="password" placeholder="Mínimo 8 caracteres" class="admin-input">
                                    </div>
                                    <div style="margin-bottom:20px;">
                                        <label class="admin-label">Confirmar senha</label>
                                        <input type="password" name="password_confirmation" placeholder="Repita a nova senha" class="admin-input">
                                    </div>
                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <button type="button" onclick="document.getElementById('modal-senha-{{ $u->id }}').style.display='none'"
                                                style="padding:9px 20px; border-radius:8px; border:1px solid var(--border); background:var(--bg-input); color:var(--text); font-size:14px; font-weight:500; cursor:pointer; font-family:inherit;">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                style="padding:9px 24px; border-radius:8px; background:linear-gradient(135deg,#05018D,var(--accent)); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer; font-family:inherit;">
                                            Salvar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:52px 16px; text-align:center; color:var(--text2); font-size:14px;">
                                Nenhum usuário cadastrado
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Formulário novo usuário --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:24px;">
        <h2 style="margin:0 0 20px; font-size:16px; font-weight:700; color:var(--text);">Novo Usuário</h2>

        @if($errors->any())
            <div style="background:var(--danger-bg); color:var(--danger-text); border:1px solid currentColor; padding:12px 14px; border-radius:8px; margin-bottom:16px; font-size:13px;">
                @foreach($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div style="margin-bottom:14px;">
                <label class="admin-label">Nome</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Nome completo" class="admin-input">
            </div>

            <div style="margin-bottom:14px;">
                <label class="admin-label">E-mail</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="email@empresa.com.br" class="admin-input">
            </div>

            <div style="margin-bottom:14px;">
                <label class="admin-label">Senha</label>
                <input type="password" name="password" placeholder="Mínimo 8 caracteres" class="admin-input">
            </div>

            <div style="margin-bottom:18px;">
                <label class="admin-label">Confirmar senha</label>
                <input type="password" name="password_confirmation" placeholder="Repita a senha" class="admin-input">
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:14px; color:var(--text2);">
                    <input type="checkbox" name="is_admin" value="1" {{ old('is_admin') ? 'checked' : '' }}
                           style="width:16px; height:16px; accent-color:var(--accent); cursor:pointer;">
                    Conceder acesso Admin
                </label>
            </div>

            <button type="submit"
                    style="width:100%; background:linear-gradient(135deg,#05018D,var(--accent)); color:#fff; border:none; border-radius:9px; padding:12px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit;">
                Criar Usuário
            </button>
        </form>
    </div>

</div>

@endsection
