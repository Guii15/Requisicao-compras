@extends('layouts.app')

@section('content')

<div style="min-height: 100vh; padding: 40px 16px; background: linear-gradient(90deg, #1d4ed8 0%, #2563eb 45%, #dc2626 100%);">
    <div style="max-width: 900px; margin: 0 auto;">
        <div style="background: #ffffff; border-radius: 18px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.15); border: 1px solid #e5e7eb;">

            <div style="background: linear-gradient(90deg, #1e3a8a 0%, #dc2626 100%); padding: 28px 32px;">
                <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; text-align: center;">
                    Formulário De Requisição De Compras
                </h1>
                <p style="margin: 10px 0 0 0; color: #dbeafe; text-align: center; font-size: 14px;">
                    Preencha os dados abaixo para solicitar uma compra ao setor responsável
                </p>
            </div>

            <div style="padding: 32px;">
                @if ($errors->any())
                    <div style="background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 16px; border-radius: 10px; margin-bottom: 24px;">
                        <ul style="margin: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li style="margin-bottom: 4px;">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('requests.store') }}" method="POST">
                    @csrf

                    <div style="display: grid; grid-template-columns: 1fr; gap: 18px;">

                        <div>
                            <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 8px;">Nome do Vendedor</label>
                            <input type="text" name="requester_name"
                                   value="{{ old('requester_name', auth()->user()->name) }}"
                                   required
                                   style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 14px 16px; font-size: 15px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 8px;">Nome do Produto</label>
                            <input type="text" name="product_name"
                                   value="{{ old('product_name') }}"
                                   required
                                   style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 14px 16px; font-size: 15px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 8px;">Código do Produto (opcional)</label>
                            <input type="text" name="product_code"
                                   value="{{ old('product_code') }}"
                                   style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 14px 16px; font-size: 15px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 8px;">Quantidade</label>
                            <input type="number" name="quantity"
                                   value="{{ old('quantity') }}"
                                   required
                                   style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 14px 16px; font-size: 15px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 8px;">Motivo</label>
                            <input type="text" name="reason"
                                   value="{{ old('reason') }}"
                                   required
                                   style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 14px 16px; font-size: 15px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 8px;">Urgência</label>
                            <select name="urgency"
                                    required
                                    style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 14px 16px; font-size: 15px; box-sizing: border-box; background: #ffffff;">
                                <option value="">Selecione a urgência</option>
                                <option value="baixa" {{ old('urgency') == 'baixa' ? 'selected' : '' }}>Baixa</option>
                                <option value="media" {{ old('urgency') == 'media' ? 'selected' : '' }}>Média</option>
                                <option value="alta" {{ old('urgency') == 'alta' ? 'selected' : '' }}>Alta</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 8px;">Justificativa</label>
                            <textarea name="justification"
                                      rows="5"
                                      required
                                      style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 14px 16px; font-size: 15px; box-sizing: border-box; resize: vertical;">{{ old('justification') }}</textarea>
                        </div>
                    </div>

                    <div style="margin-top: 28px; display: flex; justify-content: flex-end; gap: 12px; flex-wrap: wrap;">
                        <a href="{{ route('requests.index') }}"
                           style="display: inline-block; min-width: 180px; text-align: center; background: #6b7280; color: #ffffff; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 600;">
                            Cancelar
                        </a>

                        <button type="submit"
                                style="display: inline-block !important; visibility: visible !important; opacity: 1 !important; min-width: 220px; text-align: center; background: linear-gradient(90deg, #1d4ed8 0%, #dc2626 100%); color: #ffffff; padding: 12px 24px; border-radius: 10px; font-weight: 700; border: none; cursor: pointer; appearance: none; -webkit-appearance: none;">
                            Enviar Requisição
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection