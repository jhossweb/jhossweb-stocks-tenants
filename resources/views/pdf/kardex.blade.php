@extends('pdf.layout')

@section('title', 'Kardex - '.$product->pro_name)

@push('styles')
    <style>
        .info-grid {
            margin-bottom: 8px;
            padding: 10px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .info-grid table {
            width: 100%;
        }
        .info-grid td {
            padding: 3px 8px;
            font-size: 10px;
        }
        .info-grid .label {
            color: #6b7280;
            font-weight: 600;
            width: 120px;
        }
        table.data tr.initial-row {
            background: #f9fafb;
            font-weight: 600;
        }
        .badge-compra {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .badge-venta {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .badge-ajuste {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .text-green {
            color: #059669;
        }
        .text-red {
            color: #dc2626;
        }
        .text-muted {
            color: #9ca3af;
        }
    </style>
@endpush

@section('brand-sub', $product->pro_name.' ('.$product->pro_code.')')
@section('doc-title', 'Kardex de Inventario')
@section('doc-number', $product->pro_code)

@section('content')
    <div class="info-grid">
        <table>
            <tr>
                <td class="label">Producto:</td>
                <td>{{ $product->pro_name }}</td>
                <td class="label">Código:</td>
                <td>{{ $product->pro_code }}</td>
            </tr>
            <tr>
                <td class="label">Almacén:</td>
                <td>{{ $warehouse->war_name }}</td>
                <td class="label">Período:</td>
                <td>{{ $mes }} {{ $year }}</td>
            </tr>
            <tr>
                <td class="label">Unidad:</td>
                <td>{{ $product->pro_und }}</td>
                <td class="label">Factor Conv.:</td>
                <td>{{ $product->pro_conversion_factor ?? 1 }}</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Documento</th>
                <th>Tipo</th>
                <th>Contraparte</th>
                <th class="right">Entrada</th>
                <th class="right">Salida</th>
                <th class="right">Precio Unit.</th>
                <th class="right">Total Doc.</th>
                <th class="right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr class="initial-row">
                <td colspan="8">Saldo inicial</td>
                <td class="right">{{ number_format($initialBalance, 4) }}</td>
            </tr>
            @foreach($movements as $mov)
            <tr>
                <td>{{ $mov->fecha }}</td>
                <td>{{ $mov->documento }}</td>
                <td>
                    <span class="badge badge-{{ $mov->origen }}">
                        {{ $mov->tipo_transaccion ?? $mov->origen }}
                    </span>
                </td>
                <td>{{ $mov->contraparte }}</td>
                <td class="right">
                    @if($mov->entrada > 0)
                        <span class="text-green">+{{ number_format($mov->entrada, 4) }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="right">
                    @if($mov->salida > 0)
                        <span class="text-red">-{{ number_format($mov->salida, 4) }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="right">
                    @if($mov->precio_unitario > 0)
                        {{ number_format($mov->precio_unitario, 2) }}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="right">
                    @if($mov->total_documento > 0)
                        {{ number_format($mov->total_documento, 2) }}
                        @if($mov->moneda)
                            <small>{{ $mov->moneda }}</small>
                        @endif
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="right">{{ number_format($mov->saldo, 4) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection

@section('footer', 'Sistema de Inventario '.$company.' — '.date('d/m/Y H:i'))
