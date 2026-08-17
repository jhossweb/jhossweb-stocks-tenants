<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', $company)</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 20px;
        }
        h1, h2, p, table { margin: 0; }
        .right { text-align: right; }

        /* ---------- Encabezado ---------- */
        .brand-bar {
            width: 100%;
            padding: 4px 0 14px 0;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 12px;
        }
        .brand-bar table { width: 100%; }
        .brand-bar .brand-name {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #111827;
        }
        .brand-bar .brand-sub {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }
        .brand-bar .doc-title {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
            text-align: right;
            text-transform: uppercase;
            color: #6b7280;
        }
        .brand-bar .doc-number {
            display: inline-block;
            margin-top: 4px;
            text-align: right;
        }
        .doc-number span {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px 10px;
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }

        /* ---------- Tabla de datos ---------- */
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        table.data th {
            background: #374151;
            color: #ffffff;
            padding: 8px 10px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        table.data th.right { text-align: right; }
        table.data td {
            padding: 7px 10px;
            border-bottom: 1px solid #eef2f0;
            font-size: 10px;
        }
        table.data td.right { text-align: right; }
        table.data tr:nth-child(even) { background: #f8fafc; }
        table.data tr:last-child td { border-bottom: 2px solid #374151; }
        table.data .desc {
            font-weight: 600;
            color: #0f172a;
        }
        table.data .meta {
            font-size: 9px;
            color: #94a3b8;
        }

        /* ---------- Badge estado ---------- */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 600;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        /* ---------- Footer ---------- */
        .footer {
            margin-top: 26px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="brand-bar">
        <table>
            <tr>
                <td>
                    <div class="brand-name">{{ $company }}</div>
                    @hasSection('brand-sub')
                        <div class="brand-sub">@yield('brand-sub')</div>
                    @endif
                </td>
                <td style="text-align: right;">
                    @hasSection('doc-title')
                        <div class="doc-title">@yield('doc-title')</div>
                    @endif
                    @hasSection('doc-number')
                        <div class="doc-number"><span>@yield('doc-number')</span></div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @yield('content')

    <div class="footer">
        @yield('footer', 'Documento generado por '.$company.' — '.date('d/m/Y H:i'))
    </div>
</body>
</html>