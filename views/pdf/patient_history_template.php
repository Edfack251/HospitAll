<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Historial Clínico - HospitAll</title>
    <style>
        @page {
            margin: 1.5cm;
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11pt;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 20pt;
        }

        .header p {
            margin: 5px 0;
            font-size: 10pt;
            color: #7f8c8d;
        }

        .section-title {
            background-color: #f2f4f4;
            padding: 5px 10px;
            font-weight: bold;
            color: #2c3e50;
            border-left: 4px solid #2c3e50;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .info-grid {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-grid td {
            padding: 3px 0;
        }

        .label {
            font-weight: bold;
            width: 150px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #bdc3c7;
            padding: 8px;
            text-align: left;
            font-size: 10pt;
        }

        table.data-table th {
            background-color: #ecf0f1;
            color: #2c3e50;
        }

        .entry {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .entry-header {
            font-weight: bold;
            border-bottom: 1px solid #eee;
            padding-bottom: 3px;
            margin-bottom: 8px;
            color: #2980b9;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #95a5a6;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }

        .text-muted {
            color: #7f8c8d;
        }

        .badge {
            background: #e74c3c;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9pt;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>HOSPITALL V1</h1>
        <p>Sistema de Gestión Hospitalaria | Historial Clínico Consolidado</p>
        <p>Fecha de emisión:
            <?php echo date('d/m/Y H:i'); ?>
        </p>
    </div>

    <div class="section-title">DATOS DEL PACIENTE</div>
    <table class="info-grid">
        <tr>
            <td class="label">Nombre Completo:</td>
            <td>
                <?php echo htmlspecialchars($patient['nombre'] . ' ' . $patient['apellido']); ?>
            </td>
            <td class="label">Identificación:</td>
            <td>
                <?php echo htmlspecialchars($patient['identificacion']); ?> (
                <?php echo htmlspecialchars($patient['identificacion_tipo']); ?>)
            </td>
        </tr>
        <tr>
            <td class="label">Fecha Nacimiento:</td>
            <td>
                <?php echo date('d/m/Y', strtotime($patient['fecha_nacimiento'])); ?> (
                <?php echo $patient['edad']; ?> años)
            </td>
            <td class="label">Género:</td>
            <td>
                <?php echo htmlspecialchars($patient['genero']); ?>
            </td>
        </tr>
        <tr>
            <td class="label">Grupo Sanguíneo:</td>
            <td><span class="badge">
                    <?php echo htmlspecialchars($patient['grupo_sanguineo'] ?: 'N/R'); ?>
                </span></td>
            <td class="label">Teléfono:</td>
            <td>
                <?php echo htmlspecialchars($patient['telefono'] ?: 'N/R'); ?>
            </td>
        </tr>
        <tr>
            <td class="label">Alergias:</td>
            <td colspan="3">
                <?php echo htmlspecialchars($patient['alergias'] ?: 'Ninguna reportada'); ?>
            </td>
        </tr>
    </table>

    <div class="section-title">EVOLUCIÓN CLÍNICA (Últimas
        <?php echo count($history); ?> consultas)
    </div>

    <?php if (empty($history)): ?>
        <p class="text-muted">No se registran atenciones clínicas para este paciente.</p>
    <?php else: ?>
        <?php foreach ($history as $entry): ?>
            <div class="entry">
                <div class="entry-header">
                    CONSULTA -
                    <?php echo date('d/m/Y', strtotime($entry['fecha_cita'])); ?>
                    | Dr.
                    <?php echo htmlspecialchars($entry['medico_nombre'] . ' ' . $entry['medico_apellido']); ?>
                </div>

                <p><strong>Diagnóstico:</strong>
                    <?php echo nl2br(htmlspecialchars($entry['diagnostico'])); ?>
                </p>
                <p><strong>Tratamiento:</strong>
                    <?php echo nl2br(htmlspecialchars($entry['tratamiento'])); ?>
                </p>

                <?php if (!empty($entry['observaciones'])): ?>
                    <p><strong>Observaciones:</strong>
                        <?php echo nl2br(htmlspecialchars($entry['observaciones'])); ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($entry['prescriptions'])): ?>
                    <p><strong>Prescripciones:</strong></p>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th>Dosis</th>
                                <th>Frecuencia</th>
                                <th>Duración</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entry['prescriptions'] as $pres): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($pres['medicamento_nombre'] ?: $pres['medicamento_texto']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($pres['dosis']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($pres['frecuencia']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($pres['duracion']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php if (!empty($entry['laboratories'])): ?>
                    <p><strong>Estudios de Laboratorio:</strong></p>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Examen</th>
                                <th>Resultado</th>
                                <th>Rango Ref.</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entry['laboratories'] as $lab): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($lab['examen_solicitado']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($lab['resultado_examen'] ?: 'Pdte.'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($lab['rango_referencia'] ?: '-'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($lab['estado']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="footer">
        Este documento es información confidencial y propiedad del paciente. HospitAll V1. Generado electrónicamente.
    </div>
</body>

</html>