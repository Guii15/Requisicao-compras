<x-guest-layout>

<style>
.auth-input {
    width:100%; background:var(--g-input); border:1px solid var(--g-input-border);
    border-radius:10px; padding:11px 14px; font-size:14px; color:var(--g-input-text);
    font-family:inherit; outline:none; transition:border-color 0.2s,box-shadow 0.2s,background 0.3s;
}
.auth-input:focus { border-color:var(--g-accent); box-shadow:0 0 0 3px rgba(0,113,227,0.2); }
.auth-input::placeholder { color:var(--g-input-placeholder); }
.auth-input:-webkit-autofill,
.auth-input:-webkit-autofill:hover,
.auth-input:-webkit-autofill:focus,
.auth-input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 1000px var(--g-autofill) inset !important;
    -webkit-text-fill-color: var(--g-input-text) !important;
    caret-color: var(--g-input-text);
    transition: background-color 5000s ease-in-out 0s;
}
.auth-label {
    display:block; font-size:11px; font-weight:700; color:var(--g-label);
    margin-bottom:7px; text-transform:uppercase; letter-spacing:0.6px;
}
</style>

<h2 style="font-size:22px; font-weight:700; color:var(--g-text); margin:0 0 4px; letter-spacing:-0.3px;">Bem-vindo!</h2>
<p style="font-size:13px; color:var(--g-text2); margin:0 0 28px;">Faça login para acessar o sistema</p>

<x-auth-session-status class="mb-4" :status="session('status')" />

@if ($errors->any())
    <div style="background:var(--g-danger-bg); color:var(--g-danger); border:1px solid var(--g-danger-border); padding:12px 15px; border-radius:10px; margin-bottom:20px; font-size:13px;">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div style="margin-bottom:18px;">
        <label class="auth-label">E-mail <span style="color:var(--g-danger);">*</span></label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="auth-input">
    </div>

    <div style="margin-bottom:18px;">
        <label class="auth-label">Senha <span style="color:var(--g-danger);">*</span></label>
        <input type="password" name="password" required autocomplete="current-password" class="auth-input">
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:26px;">
        <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--g-text2); cursor:pointer;">
            <input type="checkbox" name="remember" style="width:15px; height:15px; accent-color:var(--g-accent); cursor:pointer;">
            Lembrar de mim
        </label>
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" style="font-size:13px; color:var(--g-accent); text-decoration:none; font-weight:500;">
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
