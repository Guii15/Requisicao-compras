@extends('layouts.app')

@section('content')

@php
$inputStyle = "width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 13px; font-size:14px; color:#374151; box-sizing:border-box; outline:none; background:#fff;";
$labelStyle = "display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;";
@endphp

<div style="max-width:560px; margin:0 auto;">

    {{-- Cabeçalho --}}
    <div style="margin-bottom:24px;">
        <h1 style="margin:0; font-size:22px; font-weight:700; color:#05018D;">Meu Perfil</h1>
        <p style="margin:4px 0 0; color:#9ca3af; font-size:13px;">Atualize seus dados de acesso</p>
    </div>

    {{-- Mensagens de sucesso --}}
    @if(session('status') === 'profile-updated')
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:11px 15px; border-radius:8px; margin-bottom:16px; font-size:13px;">
            ✓ Perfil atualizado com sucesso.
        </div>
    @endif
    @if(session('status') === 'password-updated')
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:11px 15px; border-radius:8px; margin-bottom:16px; font-size:13px;">
            ✓ Senha alterada com sucesso.
        </div>
    @endif

    {{-- Card: Informações --}}
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; margin-bottom:20px; box-shadow:0 1px 4px rgba(0,0,0,0.05);">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid #f3f4f6;">
            <div style="width:40px; height:40px; background:linear-gradient(135deg,#05018D,#b40000); border-radius:50%; display:flex; align-items:center; justify-content:center;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:20px; height:20px; color:#fff;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div>
                <p style="margin:0; font-size:15px; font-weight:700; color:#111827;">{{ Auth::user()->name }}</p>
                <p style="margin:0; font-size:13px; color:#9ca3af;">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')

            @if($errors->get('name') || $errors->get('email'))
                <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:13px;">
                    @foreach(array_merge($errors->get('name') ?? [], $errors->get('email') ?? []) as $e)
                        <div>{{ $e }}</div>
                    @endforeach
                </div>
            @endif

            <div style="margin-bottom:16px;">
                <label style="{{ $labelStyle }}">Nome</label>
                <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}" required
                       style="{{ $inputStyle }}"
                       onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                       onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
            </div>

            <div style="margin-bottom:20px;">
                <label style="{{ $labelStyle }}">E-mail</label>
                <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}" required
                       style="{{ $inputStyle }}"
                       onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                       onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
            </div>

            <button type="submit"
                    style="padding:10px 24px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; border-radius:8px; cursor:pointer;">
                Salvar alterações
            </button>
        </form>
    </div>

    {{-- Card: Senha --}}
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,0.05);">
        <p style="margin:0 0 20px; font-size:15px; font-weight:700; color:#111827; padding-bottom:16px; border-bottom:1px solid #f3f4f6;">Alterar Senha</p>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')

            @if($errors->updatePassword->any())
                <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:13px;">
                    @foreach($errors->updatePassword->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            @endif

            <div style="margin-bottom:16px;">
                <label style="{{ $labelStyle }}">Senha atual</label>
                <input type="password" name="current_password" autocomplete="current-password"
                       style="{{ $inputStyle }}"
                       onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                       onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
            </div>

            <div style="margin-bottom:16px;">
                <label style="{{ $labelStyle }}">Nova senha</label>
                <input type="password" name="password" autocomplete="new-password"
                       style="{{ $inputStyle }}"
                       onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                       onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
            </div>

            <div style="margin-bottom:20px;">
                <label style="{{ $labelStyle }}">Confirmar nova senha</label>
                <input type="password" name="password_confirmation" autocomplete="new-password"
                       style="{{ $inputStyle }}"
                       onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                       onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
            </div>

            <button type="submit"
                    style="padding:10px 24px; background:#05018D; color:#fff; font-size:14px; font-weight:700; border:none; border-radius:8px; cursor:pointer;">
                Alterar senha
            </button>
        </form>
    </div>

</div>

@endsection
