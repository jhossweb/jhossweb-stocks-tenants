<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ $payment->receipt_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 16px;
        }
        h1, h2, p, table { margin: 0; }
        .right { text-align: right; }
        .brand-bar {
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 12px;
        }
        .brand-name {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }
        .brand-sub {
            font-size: 9px;
            color: #6b7280;
            margin-top: 2px;
        }
        .doc-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-align: right;
            text-transform: uppercase;
            color: #6b7280;
        }
        .doc-number span {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 700;
            color: #111827;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data th {
            background: #374151;
            color: #ffffff;
            padding: 6px 8px;
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
        }
        table.data td {
            padding: 6px 8px;
            border-bottom: 1px solid #eef2f0;
            font-size: 10px;
        }
        table.data td.right { text-align: right; }
        .total {
            font-weight: 700;
            color: #0f172a;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: 600;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .footer {
            margin-top: 22px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="brand-bar">
        <table width="100%">
            <tr>
                <td>
                    <div class="brand-name">Vidrialum ERP</div>
                    <div class="brand-sub">Plataforma SaaS · Recibo de cobro</div>
                </td>
                <td class="right">
                    <div class="doc-title">Recibo</div>
                    <div class="doc-number"><span>{{ $payment->receipt_number }}</span></div>
                </td>
            </tr>
        </table>
    </div>

    <table class="data">
        <tr>
            <th>Fecha</th>
            <td>{{ ($payment->paid_at ?? $payment->created_at)?->format('d/m/Y') }}</td>
            <th>Estado</th>
            <td><span class="badge">{{ strtoupper($payment->status) }}</span></td>
        </tr>
        <tr>
            <th>Cliente</th>
            <td>{{ $payment->tenant?->name }}</td>
            <th>RIF</th>
            <td>{{ $payment->tenant?->rif ?: '—' }}</td>
        </tr>
        <tr>
            <th>Subdominio</th>
            <td>{{ $payment->tenant?->domain() ?: '—' }}</td>
            <th>Plan</th>
            <td>{{ $payment->subscription?->plan?->name ?: '—' }}</td>
        </tr>
    </table>

    <table class="data">
        <tr>
            <th>Monto cobrado</th>
            <th class="right">Tasa aplicada</th>
            <th class="right">Equivalente USD</th>
        </tr>
        <tr>
            <td>{{ number_format((float) $payment->amount, 2, ',', '.') }} {{ $payment->currency }}</td>
            <td class="right">{{ number_format((float) $payment->exchange_rate, 4, ',', '.') }}</td>
            <td class="right total">$ {{ number_format((float) $payment->amount_usd, 2, ',', '.') }}</td>
        </tr>
    </table>

    <table class="data">
        <tr>
            <th>Método</th>
            <td>{{ $payment->method }}</td>
            <th>Referencia</th>
            <td>{{ $payment->reference ?: '—' }}</td>
        </tr>
        <tr>
            <th>Registrado por</th>
            <td>{{ $payment->recordedBy?->name ?: '—' }}</td>
            <th>Notas</th>
            <td>{{ $payment->notes ?: '—' }}</td>
        </tr>
    </table>

    <div class="footer">
        Documento generado por Vidrialum ERP — {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>
