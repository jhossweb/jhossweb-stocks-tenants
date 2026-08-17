@extends('pdf.layout')

@section('title', 'Estado de Cuenta - '.$customer->clt_name)

@push('styles')
    <style>
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

        .days-positive {
            color: #047857;
            font-weight: 600;
        }
        .days-negative {
            color: #b91c1c;
            font-weight: 700;
        }
        .days-zero {
            color: #b45309;
            font-weight: 600;
        }
        .text-muted {
            color: #94a3b8;
        }

        .totals-block {
            margin-top: 14px;
        }
        .totals-block .t-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
            margin-bottom: 4px;
        }
    </style>
@endpush

@section('brand-sub', $customer->clt_name)
@section('doc-title', 'Estado de Cuenta - Cobranza')
@section('doc-number', $customer->clt_code ?? '#'.$customer->id)

@section('content')
    <table class="info-wrap">
        <tbody>
            <tr>
                <td style="width: 50%;">
                    <div class="box">
                        <div class="box-title">Cliente</div>
                        <table class="kv">
                            <tr>
                                <td class="label">Código</td>
                                <td class="value">{{ $customer->clt_code ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Cédula</td>
                                <td class="value">{{ $customer->clt_cedula ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Nombre</td>
                                <td class="value">{{ $customer->clt_name }}</td>
                            </tr>
                            <tr>
                                <td class="label">Teléfono</td>
                                <td class="value">{{ $customer->clt_phone ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Email</td>
                                <td class="value">{{ $customer->clt_email ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Dirección</td>
                                <td class="value">{{ $customer->clt_address ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <div class="box">
                        <div class="box-title">Información del reporte</div>
                        <table class="kv">
                            <tr>
                                <td class="label">Generado</td>
                                <td class="value">{{ $generatedAt }}</td>
                            </tr>
                            <tr>
                                <td class="label">Documentos</td>
                                <td class="value">{{ $accounts->count() }} pendiente(s)</td>
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
                <th>Documento</th>
                <th>Fecha</th>
                <th>Vencimiento</th>
                <th class="right">Días Créd.</th>
                <th class="right">Días Rest.</th>
                <th class="right">Total</th>
                <th class="right">Saldo</th>
                <th>Moneda</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($accounts as $account)
            <tr>
                <td>
                    <div class="desc">{{ $account->saleOrder?->sls_number ?? $account->acr_sls_number }}</div>
                    @if ($account->saleOrder?->transactionType?->trans_type_description)
                        <div class="meta">{{ $account->saleOrder->transactionType->trans_type_description }}</div>
                    @endif
                </td>
                <td>{{ $account->saleOrder?->sls_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $account->saleOrder?->sls_due_date?->format('d/m/Y') ?? '—' }}</td>
                <td class="right">{{ (int) $account->saleOrder?->sls_credit_days }}</td>
                <td class="right">
                    @php
                        $days = (int) $account->saleOrder?->days_remaining;
                        $class = $days > 0 ? 'days-positive' : ($days < 0 ? 'days-negative' : 'days-zero');
                        $label = $days > 0 ? $days.' días' : ($days < 0 ? $days.' vencido' : 'Vence hoy');
                    @endphp
                    <span class="{{ $class }}">{{ $label }}</span>
                </td>
                <td class="right">{{ number_format((float) $account->acr_total, 4) }}</td>
                <td class="right">{{ number_format((float) $account->acr_balance, 4) }}</td>
                <td>{{ $account->saleOrder?->sls_coin ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; color: #94a3b8; padding: 14px;">
                    El cliente no tiene documentos pendientes
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals-block">
        <div class="t-title">Saldos pendientes por moneda</div>
        <table class="data">
            <tbody>
                @forelse ($totals as $coin => $total)
                <tr>
                    <td class="desc" style="width: 50%;">{{ $coin }}</td>
                    <td class="right" style="font-weight: 600;">{{ number_format($total, 4) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" class="text-muted" style="text-align: center;">Sin saldos</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
