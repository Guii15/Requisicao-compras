<x-guest-layout>

<style>
.auth-input {
    width:100%; background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.14);
    border-radius:10px; padding:11px 14px; font-size:14px; color:#f5f5f7;
    font-family:inherit; outline:none; transition:border-color 0.2s,box-shadow 0.2s;
}
.auth-input:focus { border-color:#0071e3; box-shadow:0 0 0 3px rgba(0,113,227,0.2); }
.auth-input::placeholder { color:rgba(245,245,247,0.3); }
.auth-label {
    display:block; font-size:11px; font-weight:700; color:rgba(245,245,247,0.45);
    margin-bottom:7px; text-transform:uppercase; letter-spacing:0.6px;
}
</style>

<h2 style="font-size:22px; font-weight:700; color:#f5f5f7; margin:0 0 4px; letter-spacing:-0.3px;">Bem-vindo!</h2>
<p style="font-size:13px; color:rgba(245,245,247,0.4); margin:0 0 28px;">Faça login para acessar o sistema</p>

<x-auth-session-status class="mb-4" :status="session('status')" />

@if ($errors->any())
    <div style="background:rgba(255,69,58,0.12); color:#ff6b6b; border:1px solid rgba(255,69,58,0.25); padding:12px 15px; border-radius:10px; margin-bottom:20px; font-size:13px;">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div style="margin-bottom:18px;">
        <label class="auth-label">E-mail <span style="color:#ff453a;">*</span></label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="auth-input">
    </div>

    <div style="margin-bottom:18px;">
        <label class="auth-label">Senha <span style="color:#ff453a;">*</span></label>
        <input type="password" name="password" required autocomplete="current-password" class="auth-input">
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:26px;">
        <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:rgba(245,245,247,0.45); cursor:pointer;">
            <input type="checkbox" name="remember" style="width:15px; height:15px; accent-color:#0071e3;">
            Lembrar de mim
        </label>
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" style="font-size:13px; color:#0071e3; text-decoration:none; font-weight:500;">
                Esqueceu a senha?
            </a>
        @endif
    </div>

    <button type="submit"
            style="width:100%; padding:12px; border-radius:10px; background:linear-gradient(135deg,#05018D 0%,#0071e3 100%); color:#fff; font-size:15px; font-weight:700; border:none; cursor:pointer; font-family:inherit; letter-spacing:-0.2px; box-shadow:0 4px 16px rgba(0,113,227,0.35);">
        Entrar
    </button>
</form>

</x-guest-layout>
