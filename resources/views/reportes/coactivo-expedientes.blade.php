<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Expedientes Coactivo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            margin: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2196F3;
        }
        .header h1 {
            color: #333;
            margin: 5px 0;
            font-size: 16px;
        }
        .header p {
            color: #666;
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th {
            background-color: #2196F3;
            color: white;
            padding: 6px 4px;
            text-align: left;
            border: 1px solid #1976D2;
            font-size: 9px;
        }
        table td {
            padding: 4px;
            border: 1px solid #ddd;
            font-size: 8px;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE EXPEDIENTES COACTIVOS</h1>
        <p>Municipalidad Distrital de Nuevo Chimbote</p>
        <p>Fecha de generación: {{ $fecha_generacion }}</p>
        @if(!empty($filtros['estado']))
        <p>Estado: {{ $filtros['estado'] }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Expediente</th>
                <th>Administrado</th>
                <th>Documento</th>
                <th>Estado</th>
                <th style="text-align: right;">Deuda</th>
                <th style="text-align: right;">Pagado</th>
                <th style="text-align: right;">Pendiente</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expedientes as $exp)
            @php
                $admin = $exp->expediente->administrado ?? null;
                $nombreCompleto = $admin 
                    ? trim(($admin->nombres ?? '') . ' ' . ($admin->apellidos ?? '')) ?: ($admin->razon_social ?? 'Sin nombre')
                    : 'Sin administrado';
                $documento = $admin ? ($admin->dni ?: ($admin->ruc ?? '')) : '';
                $montoTotal = ($exp->monto_deuda ?? 0) + ($exp->monto_costas ?? 0) + ($exp->monto_gastos_admin ?? 0);
                $montoPagado = $exp->monto_pagado ?? 0;
                $pendiente = $montoTotal - $montoPagado;
            @endphp
            <tr>
                <td>{{ $exp->codigo_expediente_coactivo }}</td>
                <td>{{ $exp->expediente->codigo_expediente ?? '' }}</td>
                <td>{{ $nombreCompleto }}</td>
                <td>{{ $documento }}</td>
                <td>{{ $exp->estado }}</td>
                <td style="text-align: right;">S/ {{ number_format($montoTotal, 2) }}</td>
                <td style="text-align: right;">S/ {{ number_format($montoPagado, 2) }}</td>
                <td style="text-align: right;">S/ {{ number_format($pendiente, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>© Municipalidad Distrital de Nuevo Chimbote - {{ date('Y') }}</p>
    </div>
</body>
</html>
