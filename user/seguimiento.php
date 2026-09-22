<?php
session_start();
require '../../ogistic/php/db_connect.php';

// Verificar sesión y permisos
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../ogistic/index.html');
    exit;
}

// Obtener dispositivos asociados al cliente
$dispositivos = [];
try {
    $stmt = $conn->prepare("
        SELECT a.id_dispositivo, a.codigo_activacion, a.estado 
        FROM activaciones a 
        WHERE a.id_cliente = :user_id AND a.estado = 'activo'
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $dispositivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener dispositivos: " . $e->getMessage());
}

// Procesar filtros
$id_dispositivo = $_GET['id-dispositivo'] ?? null;
$fecha_inicio = $_GET['fecha-inicio'] ?? date('Y-m-d', strtotime('-7 days'));
$fecha_fin = $_GET['fecha-fin'] ?? date('Y-m-d');

// Obtener datos del dispositivo si hay filtro
$datos_dispositivo = [];
$alertas = [];

if ($id_dispositivo) {
    try {
        // Verificar que el dispositivo pertenece al usuario
        $stmt = $conn->prepare("
            SELECT 1 FROM activaciones 
            WHERE id_dispositivo = :id_dispositivo 
            AND id_cliente = :user_id
        ");
        $stmt->bindParam(':id_dispositivo', $id_dispositivo);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Obtener datos biométricos (usando fecha_activacion como referencia)
            $stmt = $conn->prepare("
                SELECT d.*, a.fecha_activacion as fecha_referencia
                FROM dispositivo d
                JOIN activaciones a ON d.ID_Dispositivo = a.id_dispositivo
                WHERE d.ID_Dispositivo = :id_dispositivo
                AND a.fecha_activacion BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY a.fecha_activacion DESC
            ");
            $stmt->bindParam(':id_dispositivo', $id_dispositivo);
            $stmt->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt->bindParam(':fecha_fin', $fecha_fin);
            $stmt->execute();
            $datos_dispositivo = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener alertas con información de conductor/camión
            $stmt = $conn->prepare("
                SELECT 
                    a.ID_Alerta,
                    a.TipoAlerta,
                    a.Fecha,
                    a.Hora,
                    a.ID_Dispositivo,
                    c.NombreCompleto as Conductor,
                    cam.Matricula as Camion
                FROM alerta a
                LEFT JOIN conductor c ON a.ID_Conductor = c.ID_Conductor
                LEFT JOIN camion cam ON a.ID_Camion = cam.ID_Camion
                WHERE a.ID_Dispositivo = :id_dispositivo
                AND a.Fecha BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY a.Fecha DESC, a.Hora DESC
            ");
            $stmt->bindParam(':id_dispositivo', $id_dispositivo);
            $stmt->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt->bindParam(':fecha_fin', $fecha_fin);
            $stmt->execute();
            $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        die("Error al obtener datos: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="Dashboard de monitoreo para conductores" />
    <meta name="author" content="" />
    <title>IXITIA - Dashboard</title>
    <link rel="icon" href="../../ogistic/images/ixitianuevoSolo.png">
    <!-- BOOTSTRAP CORE STYLE -->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONT AWESOME STYLE -->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLE -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <!-- DATATABLE CSS -->
    <link href="assets/js/dataTables/dataTables.bootstrap.css" rel="stylesheet" />
</head>
<body>
    <!-- BARRA SUPERIOR -->
    <div class="navbar navbar-inverse set-radius-zero">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="index.html">
                    <img src="assets/img/logo2.png" alt="IXITIA" />
                </a>
            </div>
            <div class="right-div">
                <a href="../../ogistic/index.html" class="btn btn-success pull-right">CERRAR SESIÓN</a>
            </div>
        </div>
    </div>

    <!-- MENÚ PRINCIPAL -->
    <section class="menu-section">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="navbar-collapse collapse">
                        <ul id="menu-top" class="nav navbar-nav navbar-right">
                            <li><a href="index.php">DASHBOARD</a></li>
                            <li><a href="comprar.php">Adquiere tu producto</a></li>
                            <li><a href="activar.php">ACTIVAR SERVICIO</a></li>
                            <li><a href="seguimiento.php" class="menu-top-active">SEGUIMIENTO</a></li>
                            <li><a href="cuenta.php">MI CUENTA</a></li>
                            <li><a href="accesoAdministrador.php">Administrador de flota</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="content-wrapper">
        <div class="container">
            <!-- Título y Filtros -->
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">SEGUIMIENTO Y REPORTES</h4>
                </div>
            </div>

            <!-- Filtros por Fecha/ID -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <form method="get" class="form-inline">
                                <div class="form-group">
                                    <label for="id-dispositivo">Dispositivo:</label>
                                    <select class="form-control" id="id-dispositivo" name="id-dispositivo">
                                        <option value="">Seleccione un dispositivo</option>
                                        <?php foreach ($dispositivos as $dispositivo): ?>
                                            <option value="<?= htmlspecialchars($dispositivo['id_dispositivo']) ?>" 
                                                <?= ($id_dispositivo == $dispositivo['id_dispositivo']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dispositivo['codigo_activacion']) ?> 
                                                (<?= htmlspecialchars($dispositivo['id_dispositivo']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="fecha-inicio">Desde:</label>
                                    <input type="date" class="form-control" id="fecha-inicio" name="fecha-inicio"
                                           value="<?= htmlspecialchars($fecha_inicio) ?>">
                                </div>
                                <div class="form-group">
                                    <label for="fecha-fin">Hasta:</label>
                                    <input type="date" class="form-control" id="fecha-fin" name="fecha-fin"
                                           value="<?= htmlspecialchars($fecha_fin) ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Filtrar
                                </button>
                                <?php if ($id_dispositivo): ?>
                                    <button id="generar-pdf" class="btn btn-danger pull-right">
                                        <i class="fa fa-file-pdf-o"></i> Generar PDF
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestañas de Datos -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <ul class="nav nav-tabs">
                                <li class="active"><a href="#datos" data-toggle="tab">Datos Crudos</a></li>
                                <li><a href="#alertas" data-toggle="tab">Alertas</a></li>
                                <li><a href="#graficos" data-toggle="tab">Gráficos</a></li>
                            </ul>
                        </div>
                        <div class="panel-body">
                            <div class="tab-content">
                                <!-- Tab 1: Datos Crudos -->
                                <div class="tab-pane fade in active" id="datos">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover" id="tabla-datos">
                                            <thead>
                                                <tr>
                                                    <th>Fecha/Hora</th>
                                                    <th>Frec. Parpadeo (Hz)</th>
                                                    <th>Duración Parpadeo (ms)</th>
                                                    <th>Posición Cabeza</th>
                                                    <th>Bostezos (x/min)</th>
                                                    <th>Pulsaciones (BPM)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($datos_dispositivo as $dato): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($dato['fecha_referencia'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($dato['FrecuenciaParpadeo'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($dato['DuracionParpadeos'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($dato['PosicionCabeza'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($dato['FrecuenciaBostezo'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($dato['FrecuenciaPulsaciones'] ?? 'N/A') ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Tab 2: Alertas -->
                                <div class="tab-pane fade" id="alertas">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover" id="tabla-alertas">
                                            <thead>
                                                <tr>
                                                    <th>Fecha/Hora</th>
                                                    <th>Tipo Alerta</th>
                                                    <th>Dispositivo</th>
                                                    <th>Conductor</th>
                                                    <th>Camión</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($alertas as $alerta): 
                                                    // Determinar clase CSS basada en tipo de alerta
                                                    $clase = '';
                                                    if (stripos($alerta['TipoAlerta'], 'sever') !== false) $clase = 'danger';
                                                    elseif (stripos($alerta['TipoAlerta'], 'leve') !== false) $clase = 'warning';
                                                ?>
                                                <tr class="<?= $clase ?>">
                                                    <td><?= htmlspecialchars($alerta['Fecha'] ?? 'N/A') ?> <?= htmlspecialchars($alerta['Hora'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($alerta['TipoAlerta'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($alerta['ID_Dispositivo'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($alerta['Conductor'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($alerta['Camion'] ?? 'N/A') ?></td>
                                                    <td>
                                                        <button class="btn btn-xs btn-info btn-detalle" 
                                                                data-id="<?= htmlspecialchars($alerta['ID_Alerta']) ?>">
                                                            <i class="fa fa-info-circle"></i> Detalles
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Tab 3: Gráficos -->
                                <div class="tab-pane fade" id="graficos">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="panel panel-default">
                                                <div class="panel-heading">
                                                    <i class="fa fa-bar-chart"></i> Frecuencia de Parpadeo
                                                </div>
                                                <div class="panel-body">
                                                    <canvas id="grafico-parpadeo" style="height: 250px;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="panel panel-default">
                                                <div class="panel-heading">
                                                    <i class="fa fa-line-chart"></i> Bostezos por Hora
                                                </div>
                                                <div class="panel-body">
                                                    <canvas id="grafico-bostezos" style="height: 250px;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalles de alerta -->
    <div class="modal fade" id="modalDetalle" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Detalles de Alerta</h4>
                </div>
                <div class="modal-body" id="detalle-alerta">
                    Cargando detalles...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <section class="footer-section">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    &copy; 2024 IXITIA | Monitoreo de Conductores
                </div>
            </div>
        </div>
    </section>

    <!-- SCRIPTS -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <!-- DATATABLE SCRIPTS -->
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap.js"></script>
    <!-- CHART JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- PDFMAKE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.68/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.68/vfs_fonts.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar DataTables
            $('#tabla-datos').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
                }
            });
            
            $('#tabla-alertas').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
                }
            });

            // Gráficos con datos reales
            <?php if (!empty($datos_dispositivo)): ?>
                // Preparar datos para gráficos
                const fechas = <?= json_encode(array_column($datos_dispositivo, 'fecha_referencia')) ?>;
                const parpadeos = <?= json_encode(array_column($datos_dispositivo, 'FrecuenciaParpadeo')) ?>;
                const bostezos = <?= json_encode(array_column($datos_dispositivo, 'FrecuenciaBostezo')) ?>;
                
                // Gráfico 1: Frecuencia de Parpadeo
                const ctx1 = document.getElementById('grafico-parpadeo').getContext('2d');
                new Chart(ctx1, {
                    type: 'line',
                    data: {
                        labels: fechas,
                        datasets: [{
                            label: 'Frecuencia (Hz)',
                            data: parpadeos,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.3
                        }]
                    },
                    options: {
                        scales: {
                            x: {
                                ticks: {
                                    maxTicksLimit: 10
                                }
                            }
                        }
                    }
                });

                // Gráfico 2: Bostezos
                const ctx2 = document.getElementById('grafico-bostezos').getContext('2d');
                new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: fechas,
                        datasets: [{
                            label: 'Bostezos (x/min)',
                            data: bostezos,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)'
                        }]
                    },
                    options: {
                        scales: {
                            x: {
                                ticks: {
                                    maxTicksLimit: 10
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>

            // Detalles de alerta
            $('.btn-detalle').click(function() {
                const idAlerta = $(this).data('id');
                $.get('detalle_alerta.php?id=' + idAlerta, function(data) {
                    $('#detalle-alerta').html(data);
                    $('#modalDetalle').modal('show');
                });
            });

            // Generar PDF
            $('#generar-pdf').click(function(e) {
                e.preventDefault();
                
                const docDefinition = {
                    content: [
                        { text: 'REPORTE IXITIA', style: 'header' },
                        { text: 'Datos del Dispositivo: <?= $id_dispositivo ?>', style: 'subheader' },
                        { text: 'Periodo: <?= $fecha_inicio ?> al <?= $fecha_fin ?>', style: 'subheader' },
                        { 
                            table: {
                                headerRows: 1,
                                widths: ['*', '*', '*', '*'],
                                body: [
                                    ['Fecha', 'Hora', 'Tipo Alerta', 'Conductor'],
                                    <?php foreach($alertas as $alerta): ?>
                                        [
                                            '<?= $alerta['Fecha'] ?? 'N/A' ?>',
                                            '<?= $alerta['Hora'] ?? 'N/A' ?>',
                                            '<?= $alerta['TipoAlerta'] ?? 'N/A' ?>',
                                            '<?= $alerta['Conductor'] ?? 'N/A' ?>'
                                        ],
                                    <?php endforeach; ?>
                                ]
                            }
                        }
                    ],
                    styles: {
                        header: { fontSize: 18, bold: true, margin: [0, 0, 0, 10] },
                        subheader: { fontSize: 14, bold: true, margin: [0, 10, 0, 5] }
                    }
                };
                pdfMake.createPdf(docDefinition).open();
            });
        });
    </script>
</body>
</html>