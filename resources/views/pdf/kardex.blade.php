<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Kardex - {{ $product->pro_name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 20px;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #111827;
        }
        .subtitle {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 20px;
        }
        .info-grid {
            margin-bottom: 20px;
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
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data th {
            background: #f3f4f6;
            padding: 6px 8px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
        }
        table.data th.right {
            text-align: right;
        }
        table.data td {
            padding: 5px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        table.data td.right {
            text-align: right;
        }
        table.data tr.initial-row {
            background: #f9fafb;
            font-weight: 600;
        }
        table.data tr:nth-child(even) {
            background: #fafafa;
        }
        .badge {
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 9px;
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
        .footer {
            margin-top: 30px;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Kardex de Inventario</h1>
    <p class="subtitle">{{ $product->pro_name }} ({{ $product->pro_code }})</p>

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

    <div class="footer">
        Generado el {{ date('d/m/Y H:i') }} — Sistema de Inventario Vidrialum
    </div>
</body>
</html>
