<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            color: #212529;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007BFF;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007BFF;
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            color: #6C757D;
            font-size: 14px;
        }
        .metadata {
            width: 100%;
            margin-bottom: 30px;
            background-color: #F8F9FA;
            padding: 15px;
            border-radius: 8px;
        }
        .metadata td {
            padding: 5px 10px;
        }
        .metadata .label {
            font-weight: bold;
            color: #495057;
        }
        .section-title {
            background-color: #E9ECEF;
            padding: 10px;
            border-left: 5px solid #007BFF;
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .metrics-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .metric-card {
            width: 31%;
            display: inline-block;
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-right: 2%;
            margin-bottom: 20px;
            text-align: center;
            vertical-align: top;
        }
        .metric-card.last {
            margin-right: 0;
        }
        .metric-value {
            font-size: 22px;
            font-weight: bold;
            color: #007BFF;
            margin-bottom: 5px;
        }
        .metric-label {
            font-size: 12px;
            color: #6C757D;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .footer {
            position: fixed;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            color: #ADB5BD;
            font-size: 10px;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }
        .total-revenue {
            color: #28A745;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>HospitAll</h1>
        <p>Reporte Administrativo Mensual</p>
    </div>

    <div class="metadata">
        <table width="100%">
            <tr>
                <td class="label">Periodo Reportado:</td>
                <td><?php echo $month_name . ' ' . $year; ?></td>
                <td class="label">Fecha Generación:</td>
                <td><?php echo $timestamp; ?></td>
            </tr>
            <tr>
                <td class="label">Generado por:</td>
                <td><?php echo $generated_by; ?></td>
                <td class="label">Sistema:</td>
                <td>HospitAll HIS V1</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Resumen General de Operaciones</div>

    <div class="metric-card">
        <div class="metric-value total-revenue">$<?php echo number_format($metrics['revenue'], 2); ?></div>
        <div class="metric-label">Ingresos Totales</div>
    </div>

    <div class="metric-card">
        <div class="metric-value"><?php echo $metrics['appointments']; ?></div>
        <div class="metric-label">Citas Atendidas</div>
    </div>

    <div class="metric-card last">
        <div class="metric-value"><?php echo $metrics['new_patients']; ?></div>
        <div class="metric-label">Pacientes Nuevos</div>
    </div>

    <div class="metric-card">
        <div class="metric-value"><?php echo $metrics['pharmacy_sales']; ?></div>
        <div class="metric-label">Ventas Farmacia</div>
    </div>

    <div class="metric-card">
        <div class="metric-value"><?php echo $metrics['lab_tests']; ?></div>
        <div class="metric-label">Pruebas Lab.</div>
    </div>

    <div class="section-title">Análisis por Departamento</div>
    
    <table width="100%" cellpadding="10" style="border-collapse: collapse; margin-top: 10px;">
        <thead>
            <tr style="background-color: #F8F9FA; border-bottom: 2px solid #DEE2E6;">
                <th align="left">Departamento</th>
                <th align="center">Métrica</th>
                <th align="right">Volumen/Total</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid #EEE;">
                <td>Facturación y Finanzas</td>
                <td align="center">Ingresos Recaudados (Pagada)</td>
                <td align="right"><strong>$<?php echo number_format($metrics['revenue'], 2); ?></strong></td>
            </tr>
            <tr style="border-bottom: 1px solid #EEE;">
                <td>Gestión de Citas</td>
                <td align="center">Consultas Médicas Finalizadas</td>
                <td align="right"><?php echo $metrics['appointments']; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #EEE;">
                <td>Admisión de Pacientes</td>
                <td align="center">Registros de Nuevos Usuarios</td>
                <td align="right"><?php echo $metrics['new_patients']; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #EEE;">
                <td>Farmacia de Turno</td>
                <td align="center">Dispensaciones Realizadas</td>
                <td align="right"><?php echo $metrics['pharmacy_sales']; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #EEE;">
                <td>Laboratorio Clínico</td>
                <td align="center">Órdenes Completadas</td>
                <td align="right"><?php echo $metrics['lab_tests']; ?></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Este documento es un reporte oficial generado por el sistema HospitAll. Reservados todos los derechos.
    </div>

</body>
</html>
