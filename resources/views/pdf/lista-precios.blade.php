@extends('pdf.layout')

@section('title', 'Lista de Precios - '.$companyName)

@section('brand-sub', 'Lista de Precios — vigente al '.$validDate)

@section('doc-title', 'LISTA DE PRECIOS')

@section('doc-number', $total.' productos')

@section('content')
    <table class="data">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th style="width: 60px;">Und.</th>
                <th class="right" style="width: 110px;">Precio USD</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['pro_code'] }}</td>
                    <td class="desc">{{ $row['pro_name'] }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ strtoupper($row['pro_und']) }}</td>
                    <td class="right">{{ number_format($row['price'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 24px 0;">
                        No hay productos con precio registrado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection

@section('footer')
    Precios expresados en dólares estadounidenses (USD) — Vigencia: {{ $validDate }} · Generado: {{ $generatedAt }}
@endsection
