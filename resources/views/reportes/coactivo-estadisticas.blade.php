<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Estadísticas Coactivo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4CAF50;
        }
        .header h1 {
            color: #333;
            margin: 5px 0;
            font-size: 18px;
        }
        .header p {
            color: #666;
            margin: 3px 0;
        }
        .section {
            margin: 20px 0;
        }
        .section-title {
            background-color: #4CAF50;
            color: white;
            padding: 8px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .metric-card {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
        }
        .metric-card .label {
            color: #666;
            font-size: 10px;
            margin-bottom: 5px;
        }
        .metric-card .value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th {
            background-color: #f5f5f5;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE ESTADÍSTICAS - MÓDULO COACTIVO</h1>
        <p>Municipalidad Distrital de Nuevo Chimbote</p>
        <p>Período: {{ $fecha_inicio }} al {{ $fecha_fin }}</p>
        <p>Fecha de generación: {{ $fecha_generacion }}</p>
    </div>

    <div class="section">
        <div class="section-title">RESUMEN FINANCIERO</div>
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="label">Total Expedientes</div>
                <div class="value">{{ $estadisticas['resumen']['total_expedientes'] }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Total Deuda</div>
                <div class="value">S/ {{ number_format($estadisticas['resumen']['monto_total_deuda'], 2) }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Total Recaudado</div>
                <div class="value" style="color: #4CAF50;">S/ {{ number_format($estadisticas['resumen']['monto_total_recaudado'], 2) }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Total Pendiente</div>
                <div class="value" style="color: #FF5722;">S/ {{ number_format($estadisticas['resumen']['monto_total_pendiente'], 2) }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Porcentaje de Recaudación</div>
                <div class="value">{{ $estadisticas['resumen']['porcentaje_recaudacion'] }}%</div>
            </div>
            <div class="metric-card">
                <div class="label">Estados</div>
                <div class="value" style="font-size: 12px;">
                    En Ejecución: {{ $estadisticas['resumen']['en_ejecucion'] }}<br>
                    Archivados: {{ $estadisticas['resumen']['archivados'] }}<br>
                    Suspendidos: {{ $estadisticas['resumen']['suspendidos'] }}
                </div>
            </div>
        </div>
    </div>

    @if(count($estadisticas['distribucion_mensual']) > 0)
    <div class="section">
        <div class="section-title">DISTRIBUCIÓN MENSUAL</div>
        <table>
            <thead>
                <tr>
                    <th>Mes</th>
                    <th style="text-align: center;">Cantidad</th>
                    <th style="text-align: right;">Monto Total</th>
                    <th style="text-align: right;">Monto Recaudado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estadisticas['distribucion_mensual'] as $item)
                <tr>
                    <td>{{ $item['mes'] }}</td>
                    <td style="text-align: center;">{{ $item['cantidad'] }}</td>
                    <td style="text-align: right;">S/ {{ number_format($item['monto_total'], 2) }}</td>
                    <td style="text-align: right;">S/ {{ number_format($item['monto_recaudado'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(count($estadisticas['mayores_deudas']) > 0)
    <div class="section">
        <div class="section-title">TOP 5 MAYORES DEUDAS PENDIENTES</div>
        <table>
            <thead>
                <tr>
                    <th>Código Expediente</th>
                    <th>Administrado</th>
                    <th style="text-align: right;">Deuda Pendiente</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estadisticas['mayores_deudas'] as $deuda)
                <tr>
                    <td>{{ $deuda['codigo'] }}</td>
                    <td>{{ $deuda['administrado'] }}</td>
                    <td style="text-align: right; color: #FF5722; font-weight: bold;">
                        S/ {{ number_format($deuda['deuda_pendiente'], 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Este reporte fue generado automáticamente por el Sistema de Fiscalización y Cobranza Coactiva</p>
        <p>© Municipalidad Distrital de Nuevo Chimbote - {{ date('Y') }}</p>
    </div>
</body>
</html>
