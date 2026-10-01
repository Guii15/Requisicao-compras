@props(['item', 'margem' => '12px'])

@if($item->admin_note)
    <div style="margin-bottom:{{ $margem }}; padding:10px 12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;">
        <span style="font-size:11px; font-weight:700; color:#1d4ed8; text-transform:uppercase;">Obs (Admin):</span>
        <div style="margin-top:4px; font-size:13px; color:#1e3a8a; line-height:1.5; white-space:pre-line;">{{ $item->admin_note }}</div>
    </div>
@endif
