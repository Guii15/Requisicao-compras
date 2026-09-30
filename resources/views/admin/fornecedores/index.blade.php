@extends('layouts.app')

@section('content')
@php
    $card = 'background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; box-shadow:0 1px 4px rgba(0,0,0,0.06);';
    $label = 'display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:4px; text-transform:uppercase;';
    $campo = 'width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; box-sizing:border-box; background:#fff;';
    $th = 'padding:12px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;';
@endphp

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Painel Administrativo</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Gerencie todas as requisições de compra</p>
    </div>

    @include('admin._abas')

    <div style="margin-bottom:16px;">
        <h2 style="margin:0; font-size:18px; font-weight:700; color:#111827;">Fornecedores</h2>
        <p style="margin:4px 0 0; color:#6b7280; font-size:13px;">
            Cada fornecedor aparece uma vez só. Se dois forem a mesma empresa, mescle abaixo: as compras passam para o que ficar.
        </p>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- Mesclar --}}
    <div style="{{ $card }}">
        <p style="margin:0 0 12px; font-size:13px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px;">Mesclar dois fornecedores</p>
        <form method="POST" action="{{ route('admin.fornecedores.mesclar') }}"
              class="m-empilhar" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; align-items:end;"
              onsubmit="return this.querySelector('[name=confirmar]').checked">
            @csrf
            <div>
                <label style="{{ $label }}">Este fornecedor some…</label>
                <select name="origem_id" required style="{{ $campo }}">
                    <option value="">Selecione…</option>
                    @foreach($todos as $f)
                        <option value="{{ $f->id }}" @selected(old('origem_id') == $f->id)>{{ $f->nome }} — {{ $f->compras_count }} compra(s)</option>
                    @endforeach
                </select>
                @error('origem_id') <div style="color:#dc2626; font-size:12px; margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label style="{{ $label }}">…e as compras vão para este</label>
                <select name="destino_id" required style="{{ $campo }}">
                    <option value="">Selecione…</option>
                    @foreach($todos as $f)
                        <option value="{{ $f->id }}" @selected(old('destino_id') == $f->id)>{{ $f->nome }} — {{ $f->compras_count }} compra(s)</option>
                    @endforeach
                </select>
                @error('destino_id') <div style="color:#dc2626; font-size:12px; margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <label style="grid-column:1 / -1; font-size:13px; color:#374151; display:flex; gap:8px; align-items:center; cursor:pointer;">
                <input type="checkbox" name="confirmar" value="1">
                Confirmo: o primeiro fornecedor será apagado e todas as compras dele passam para o segundo. O nome digitado originalmente em cada compra continua guardado.
            </label>
            @error('confirmar') <div style="grid-column:1 / -1; color:#dc2626; font-size:12px;">{{ $message }}</div> @enderror
            <div style="grid-column:1 / -1;">
                <button type="submit" style="background:#05018D; color:#fff; padding:9px 20px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer;">Mesclar</button>
            </div>
        </form>
    </div>

    {{-- Busca --}}
    <div style="{{ $card }}">
        <form method="GET" action="{{ route('admin.fornecedores.index') }}" class="m-busca" style="display:flex; gap:8px;">
            <input type="text" name="q" value="{{ $q }}" placeholder="Buscar fornecedor…" style="{{ $campo }} flex:1;">
            <button type="submit" style="background:#05018D; color:#fff; padding:8px 20px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer;">Buscar</button>
            @if($q !== '')
                <a href="{{ route('admin.fornecedores.index') }}" style="padding:8px 16px; border:1px solid #e5e7eb; border-radius:8px; color:#6b7280; text-decoration:none; font-size:14px;">Limpar</a>
            @endif
        </form>
    </div>

    {{-- Lista --}}
    <div class="m-cards">
        @forelse($fornecedores as $f)
            <x-mobile-card :titulo="$f->nome" :campos="[
                'Compras' => $f->compras_count . ' compra(s)',
                'Como foi digitado' => ($grafias[$f->id] ?? collect())->map(fn ($g) => '“' . $g . '”')->implode(', ') ?: null,
            ]" />
        @empty
            <div style="padding:40px 16px; text-align:center; color:#9ca3af; font-size:14px;">
                {{ $q !== '' ? 'Nenhum fornecedor encontrado.' : 'Nenhum fornecedor cadastrado ainda.' }}
            </div>
        @endforelse
        @if($fornecedores->hasPages())
            <div style="padding:12px 0;">{{ $fornecedores->links() }}</div>
        @endif
    </div>

    <div class="m-desktop" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin-bottom:16px;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                        <th style="{{ $th }}">Fornecedor</th>
                        <th style="{{ $th }}">Como foi digitado</th>
                        <th style="{{ $th }} text-align:center;">Compras</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fornecedores as $f)
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-size:14px; font-weight:600; color:#111827;">{{ $f->nome }}</td>
                            <td style="padding:12px 16px; font-size:12.5px; color:#6b7280;">
                                {{ ($grafias[$f->id] ?? collect())->map(fn ($g) => '"' . $g . '"')->implode(', ') ?: '—' }}
                            </td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151; text-align:center;">{{ $f->compras_count }} compra(s)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="padding:40px 16px; text-align:center; color:#9ca3af; font-size:14px;">
                                {{ $q !== '' ? 'Nenhum fornecedor encontrado.' : 'Nenhum fornecedor cadastrado ainda.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($fornecedores->hasPages())
            <div style="padding:12px 16px;">{{ $fornecedores->links() }}</div>
        @endif
    </div>

    {{-- Histórico --}}
    <div style="{{ $card }}">
        <p style="margin:0 0 12px; font-size:13px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px;">Mesclagens</p>
        @forelse($mesclagens as $m)
            <div style="padding:8px 0; border-bottom:1px solid #f3f4f6; font-size:13px; color:#374151;">
                <strong>{{ $m->origem_nome }}</strong> → <strong>{{ $m->destino_nome }}</strong>
                <span style="color:#6b7280;">· {{ $m->compras_afetadas }} compra(s) · por {{ $m->user?->name ?? 'usuário removido' }} em {{ $m->created_at->format('d/m/Y H:i') }}</span>
            </div>
        @empty
            <p style="margin:0; color:#9ca3af; font-size:13px;">Nenhuma mesclagem feita ainda.</p>
        @endforelse
    </div>

</div>
@endsection
