@extends('pdf.layout')

@section('title', $documentLabel.' '.$sale->sls_number.' - '.$company)

@push('styles')
    <style>
        /* ---------- Bloques de información ---------- */
        .info-wrap {
            width: 100%;
        }
        .info-wrap td {
            padding: 6px;
            width: 50%;
            vertical-align: top;
        }
        .box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            background: #ffffff;
        }
        .box-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #f1f5f9;
        }
        .kv {
            width: 100%;
            margin-bottom: 6px;
        }
        .kv td {
            padding: 1px 0;
            font-size: 10px;
            vertical-align: top;
        }
        .kv .label {
            width: 82px;
            color: #64748b;
            font-weight: 600;
        }
        .kv .value {
            color: #1e293b;
        }
        .kv .value .muted { color: #94a3b8; }

        /* ---------- Leyenda (cotizaciones) ---------- */
        .legend {
            margin-top: 14px;
            padding: 8px 12px;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            font-size: 9px;
            color: #475569;
        }

        /* ---------- Totales ---------- */
        .totals {
            margin-top: 12px;
            margin-left: auto;
            width: 260px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .totals table { width: 100%; }
        .totals td {
            padding: 6px 14px;
            font-size: 10px;
        }
        .totals .row {
            border-bottom: 1px solid #f1f5f9;
        }
        .totals .label { color: #475569; }
        .totals .right { text-align: right; }
        .totals .grand {
            background: #f1f5f9;
        }
        .totals .grand td {
            padding-top: 9px;
            padding-bottom: 9px;
            border-top: 2px solid #334155;
        }
        .totals .grand .label {
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            font-size: 10px;
        }
        .totals .grand .right {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
    </style>
@endpush

@section('brand-sub', $documentLabel.' de Venta')
@section('doc-title', $documentLabel)
@section('doc-number', $sale->sls_number)

@section('content')
    <table class="info-wrap">
        <tbody>
            <tr>
                <td style="width: 50%;">
                    <div class="box">
                        <div class="box-title">{{ $partyLabel }}</div>
                        <table class="kv">
                            <tr>
                                <td class="label">Cliente</td>
                                <td class="value">{{ $customer?->clt_name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Dirección</td>
                                <td class="value">{{ $customer?->clt_address ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Teléfono</td>
                                <td class="value">{{ $customer?->clt_phone ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Email</td>
                                <td class="value">{{ $customer?->clt_email ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <div class="box">
                        <div class="box-title">{{ $documentInfoLabel }}</div>
                        <table class="kv">
                            <tr>
                                <td class="label">Fecha</td>
                                <td class="value">{{ $sale->sls_date?->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td class="label">Moneda</td>
                                <td class="value">{{ $sale->sls_coin }}</td>
                            </tr>
                            <tr>
                                <td class="label">Tasa Cambio</td>
                                <td class="value">{{ number_format((float) $sale->sls_valorcam, 4) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Condición</td>
                                <td class="value">
                                    {{ $sale->sls_payment_condition === 'credit' ? 'Crédito' : 'Contado' }}
                                    @if ($sale->sls_payment_condition === 'credit')
                                        <span class="muted">({{ $sale->sls_credit_days }} días)</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Vencimiento</td>
                                <td class="value">{{ $sale->sls_due_date?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Estado</td>
                                <td class="value">
                                    <span class="badge badge-{{ $sale->sls_status }}">
                                        {{ ucfirst($sale->sls_status) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Und.</th>
                <th class="right">Cant.</th>
                <th class="right">Precio Unit.</th>
                <th class="right">IVA</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
            <tr>
                <td>
                    <div class="desc">{{ $item->product?->pro_name ?? $item->mvs_pro_code }}</div>
                    @if ($item->product?->pro_name)
                        <div class="meta">{{ $item->mvs_pro_code }}</div>
                    @endif
                </td>
                <td>{{ $item->product?->pro_und ?? '—' }}</td>
                <td class="right">{{ number_format((float) $item->mvs_quantity, 4) }}</td>
                <td class="right">{{ number_format((float) $item->mvs_unit_price, 4) }}</td>
                <td class="right">{{ number_format((float) $item->mvs_pro_iva, 1) }}%</td>
                <td class="right">{{ number_format((float) $item->mvs_quantity * (float) $item->mvs_unit_price, 4) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #94a3b8; padding: 14px;">
                    Sin ítems registrados
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if (! empty($legend))
        <div class="legend">{{ $legend }}</div>
    @endif

    <div class="totals">
        <table>
            <tr class="row">
                <td class="label">Subtotal</td>
                <td class="right">{{ number_format((float) $sale->sls_subtotal, 4) }}</td>
            </tr>
            <tr class="row">
                <td class="label">Exento</td>
                <td class="right">{{ number_format((float) $sale->sls_exento, 4) }}</td>
            </tr>
            <tr class="row">
                <td class="label">IVA</td>
                <td class="right">{{ number_format((float) $sale->sls_iva, 4) }}</td>
            </tr>
            <tr class="grand">
                <td class="label">Total</td>
                <td class="right">{{ number_format((float) $sale->sls_total, 4) }} {{ $sale->sls_coin }}</td>
            </tr>
        </table>
    </div>
@endsection

@section('footer', $documentLabel.' generada por '.$company.' — '.date('d/m/Y H:i'))
