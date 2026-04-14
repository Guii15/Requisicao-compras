<x-guest-layout>
    @php
    $inputStyle = "width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 13px; font-size:14px; color:#374151; box-sizing:border-box; outline:none; background:#fff; font-family:inherit;";
    $labelStyle = "display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;";
    @endphp

    <h2 style="font-size:22px; font-weight:800; color:#05018D; margin:0 0 4px;">Criar conta</h2>
    <p style="font-size:13px; color:#9ca3af; margin:0 0 28px;">Preencha os dados para se cadastrar no sistema</p>

    @if ($errors->any())
        <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:11px 15px; border-radius:8px; margin-bottom:18px; font-size:13px;">
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div style="margin-bottom:16px;">
            <label style="{{ $labelStyle }}">Nome <span style="color:#ef4444;">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                   style="{{ $inputStyle }}"
                   onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
        </div>

        <div style="margin-bottom:16px;">
            <label style="{{ $labelStyle }}">E-mail <span style="color:#ef4444;">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                   style="{{ $inputStyle }}"
                   onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
        </div>

        <div style="margin-bottom:16px;">
            <label style="{{ $labelStyle }}">Senha <span style="color:#ef4444;">*</span></label>
            <input type="password" name="password" required autocomplete="new-password"
                   style="{{ $inputStyle }}"
                   onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
        </div>

        <div style="margin-bottom:24px;">
            <label style="{{ $labelStyle }}">Confirmar Senha <span style="color:#ef4444;">*</span></label>
            <input type="password" name="password_confirmation" required autocomplete="new-password"
                   style="{{ $inputStyle }}"
                   onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
        </div>

        <button type="submit"
                style="width:100%; padding:11px; border-radius:8px; background:linear-gradient(90deg, #05018D 0%, #b40000 100%); color:#fff; font-size:15px; font-weight:700; border:none; cursor:pointer; box-shadow:0 3px 12px rgba(5,1,141,0.35); font-family:inherit;">
            Cadastrar-se
        </button>

        <p style="text-align:center; margin-top:20px; font-size:13px; color:#9ca3af;">
            Já está cadastrado?
            <a href="{{ route('login') }}" style="color:#05018D; font-weight:700; text-decoration:none;">Fazer login</a>
        </p>
    </form>
</x-guest-layout>
