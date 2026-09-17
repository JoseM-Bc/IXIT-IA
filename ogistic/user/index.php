<?php
session_start();
require '../../ogistic/php/db_connect.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../ogistic/index.html');
    exit;
}

// Obtener datos del cliente
$cliente_id = $_SESSION['user_id'];

// 1. Obtener TODOS los dispositivos activos del cliente
$dispositivos_activos = [];
$stmt = $conn->prepare("
    SELECT a.id_dispositivo, d.ID_Dispositivo, a.codigo_activacion 
    FROM activaciones a
    JOIN dispositivo d ON a.id_dispositivo = d.ID_Dispositivo
    WHERE a.id_cliente = ? AND a.estado = 'activo'
");
$stmt->execute([$cliente_id]);
$dispositivos_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Determinar dispositivo seleccionado
$dispositivo_seleccionado = $_GET['dispositivo'] ?? ($dispositivos_activos[0]['id_dispositivo'] ?? null);

// 3. Calcular métricas principales
$metricas = [
    'nivel_fatiga' => 0,
    'alertas_hoy' => 0,
    'ultima_sincronizacion' => 'Nunca',
    'dispositivo_estado' => 'Inactivo',
    'total_dispositivos' => count($dispositivos_activos)
];

if ($dispositivo_seleccionado) {
    try {
        // Nivel de fatiga promedio (últimas 24 horas)
        $stmt = $conn->prepare("
            SELECT 
                AVG(FrecuenciaParpadeo) as avg_parpadeo,
                AVG(FrecuenciaBostezo) as avg_bostezos,
                MAX(fecha_registro) as ultimo_registro,
                COUNT(*) as total_registros
            FROM dispositivo 
            WHERE ID_Dispositivo = ?
            AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$dispositivo_seleccionado]);
        $datos_dispositivo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($datos_dispositivo && $datos_dispositivo['total_registros'] > 0) {
            $metricas['nivel_fatiga'] = round(
                ($datos_dispositivo['avg_parpadeo'] * 50) + 
                ($datos_dispositivo['avg_bostezos'] * 5) +
                20, // Valor base
                1 // Decimales
            );
            
            $metricas['ultima_sincronizacion'] = $datos_dispositivo['ultimo_registro'] ? 
                (time() - strtotime($datos_dispositivo['ultimo_registro']) < 3600 ? 
                    'Hace '.floor((time() - strtotime($datos_dispositivo['ultimo_registro']))/60).' min' : 
                    'Hace '.floor((time() - strtotime($datos_dispositivo['ultimo_registro']))/3600).' horas') 
                : 'Nunca';
        }

        // Alertas hoy
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM alerta 
            WHERE ID_Dispositivo = ? 
            AND Fecha = CURDATE()
        ");
        $stmt->execute([$dispositivo_seleccionado]);
        $metricas['alertas_hoy'] = $stmt->fetchColumn();
        
        $metricas['dispositivo_estado'] = 'Activo';
    } catch (PDOException $e) {
        die("Error al obtener métricas: " . $e->getMessage());
    }
}

// 4. Obtener datos para gráficos y tablas
$datos_grafico = [];
$alertas_recientes = [];
$registros_recientes = [];

if ($dispositivo_seleccionado) {
    try {
        // Datos para gráfico por horas
        $stmt = $conn->prepare("
            SELECT 
                HOUR(fecha_registro) as hora,
                AVG(FrecuenciaParpadeo) as parpadeo,
                AVG(FrecuenciaBostezo) as bostezos,
                COUNT(*) as registros
            FROM dispositivo
            WHERE ID_Dispositivo = ?
            AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY HOUR(fecha_registro)
            HAVING registros > 0
            ORDER BY hora
        ");
        $stmt->execute([$dispositivo_seleccionado]);
        $datos_grafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Alertas recientes
        $stmt = $conn->prepare("
            SELECT 
                a.TipoAlerta,
                a.Fecha,
                a.Hora,
                c.NombreCompleto as conductor,
                cam.Matricula as camion
            FROM alerta a
            LEFT JOIN conductor c ON a.ID_Conductor = c.ID_Conductor
            LEFT JOIN camion cam ON a.ID_Camion = cam.ID_Camion
            WHERE a.ID_Dispositivo = ?
            ORDER BY a.Fecha DESC, a.Hora DESC
            LIMIT 5
        ");
        $stmt->execute([$dispositivo_seleccionado]);
        $alertas_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Registros recientes
        $stmt = $conn->prepare("
            SELECT 
                fecha_registro as fecha,
                FrecuenciaParpadeo,
                FrecuenciaBostezo,
                PosicionCabeza,
                CASE 
                    WHEN FrecuenciaParpadeo < 0.3 THEN 'Alta'
                    WHEN FrecuenciaParpadeo < 0.5 THEN 'Media'
                    ELSE 'Normal'
                END as estado
            FROM dispositivo
            WHERE ID_Dispositivo = ?
            ORDER BY fecha_registro DESC
            LIMIT 10
        ");
        $stmt->execute([$dispositivo_seleccionado]);
        $registros_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al obtener datos: " . $e->getMessage());
    }
}

// 5. Obtener resumen de todos los dispositivos
$resumen_dispositivos = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            d.ID_Dispositivo,
            a.codigo_activacion,
            COUNT(d.ID_Dispositivo) as total_registros,
            MAX(dis.fecha_registro) as ultimo_registro,
            COUNT(al.ID_Alerta) as total_alertas
        FROM dispositivo d
        JOIN activaciones a ON d.ID_Dispositivo = a.id_dispositivo
        LEFT JOIN dispositivo dis ON d.ID_Dispositivo = dis.ID_Dispositivo
        LEFT JOIN alerta al ON d.ID_Dispositivo = al.ID_Dispositivo AND al.Fecha = CURDATE()
        WHERE a.id_cliente = ?
        GROUP BY d.ID_Dispositivo, a.codigo_activacion
    ");
    $stmt->execute([$cliente_id]);
    $resumen_dispositivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener resumen de dispositivos: " . $e->getMessage());
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
                            <li><a href="index.php" class="menu-top-active">DASHBOARD</a></li>
                            <li><a href="comprar.php">Adquiere tu producto</a></li>
                            <li><a href="activar.php">ACTIVAR SERVICIO</a></li>
                            <li><a href="seguimiento.php">SEGUIMIENTO</a></li>
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
            <!-- Título con selector de dispositivo -->
            <div class="row pad-botm">
                <div class="col-md-8 col-sm-6">
                    <h4 class="header-line">PANEL DE MONITOREO</h4>
                </div>
                <div class="col-md-4 col-sm-6">
                    <?php if(count($dispositivos_activos) > 0): ?>
                    <form method="get" class="form-inline pull-right">
                        <div class="form-group">
                            <label for="dispositivo" class="sr-only">Dispositivo</label>
                            <select name="dispositivo" class="form-control" onchange="this.form.submit()">
                                <?php foreach($dispositivos_activos as $dispositivo): ?>
                                <option value="<?= $dispositivo['id_dispositivo'] ?>" 
                                    <?= $dispositivo_seleccionado == $dispositivo['id_dispositivo'] ? 'selected' : '' ?>>
                                    Dispositivo <?= $dispositivo['codigo_activacion'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Widgets de Estado -->
            <div class="row">
                <!-- Widget 1: Nivel de Fatiga -->
                <div class="col-md-3 col-sm-6 col-xs-12">
                    <div class="alert alert-<?= $metricas['nivel_fatiga'] > 70 ? 'danger' : ($metricas['nivel_fatiga'] > 40 ? 'warning' : 'success') ?> back-widget-set text-center">
                        <i class="fa fa-eye fa-5x"></i>
                        <h3><?= $dispositivo_seleccionado ? $metricas['nivel_fatiga'].'%' : 'N/A' ?></h3>
                        <p>Nivel de Fatiga</p>
                        <small>Últimas 24h</small>
                    </div>
                </div>

                <!-- Widget 2: Alertas Recientes -->
                <div class="col-md-3 col-sm-6 col-xs-12">
                    <div class="alert alert-<?= $metricas['alertas_hoy'] > 0 ? 'danger' : 'success' ?> back-widget-set text-center">
                        <i class="fa fa-bell fa-5x"></i>
                        <h3><?= $dispositivo_seleccionado ? $metricas['alertas_hoy'] : 'N/A' ?></h3>
                        <p>Alertas Hoy</p>
                        <small><?= date('d/m/Y') ?></small>
                    </div>
                </div>

                <!-- Widget 3: Última Sincronización -->
                <div class="col-md-3 col-sm-6 col-xs-12">
                    <div class="alert alert-info back-widget-set text-center">
                        <i class="fa fa-refresh fa-5x"></i>
                        <h3><?= $metricas['ultima_sincronizacion'] ?></h3>
                        <p>Última Sincronización</p>
                        <?php if(!$dispositivo_seleccionado): ?>
                        <small>Seleccione un dispositivo</small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Widget 4: Estado del Dispositivo -->
                <div class="col-md-3 col-sm-6 col-xs-12">
                    <div class="alert alert-<?= $dispositivo_seleccionado ? 'success' : 'danger' ?> back-widget-set text-center">
                        <i class="fa fa-check-circle fa-5x"></i>
                        <h3><?= $dispositivo_seleccionado ? 'Activo' : 'Inactivo' ?></h3>
                        <p>Dispositivo</p>
                        <small><?= $metricas['total_dispositivos'] ?> activos</small>
                    </div>
                </div>
            </div>

            <!-- Resumen de Dispositivos -->
            <?php if(count($resumen_dispositivos) > 1): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fa fa-cubes"></i> Resumen de Dispositivos
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Dispositivo</th>
                                            <th>Registros</th>
                                            <th>Último Registro</th>
                                            <th>Alertas Hoy</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($resumen_dispositivos as $disp): ?>
                                        <tr class="<?= $dispositivo_seleccionado == $disp['ID_Dispositivo'] ? 'info' : '' ?>">
                                            <td>
                                                <a href="?dispositivo=<?= $disp['ID_Dispositivo'] ?>">
                                                    <?= $disp['codigo_activacion'] ?>
                                                </a>
                                            </td>
                                            <td><?= $disp['total_registros'] ?></td>
                                            <td><?= $disp['ultimo_registro'] ? date('d/m H:i', strtotime($disp['ultimo_registro'])) : 'Nunca' ?></td>
                                            <td><?= $disp['total_alertas'] ?></td>
                                            <td>
                                                <span class="label label-<?= $disp['ultimo_registro'] && (time() - strtotime($disp['ultimo_registro']) < 86400) ? 'success' : 'danger' ?>">
                                                    <?= $disp['ultimo_registro'] && (time() - strtotime($disp['ultimo_registro']) < 86400) ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Gráfico y Datos Recientes -->
            <?php if($dispositivo_seleccionado): ?>
            <div class="row">
                <!-- Gráfico de Comportamiento -->
                <div class="col-md-8 col-sm-8 col-xs-12">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-bar-chart-o"></i> Comportamiento (Últimas 24h)
                        </div>
                        <div class="panel-body">
                            <?php if(empty($datos_grafico)): ?>
                            <div class="alert alert-info">No hay datos recientes para mostrar</div>
                            <?php else: ?>
                            <canvas id="graficoCanvas" height="300"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Alertas Recientes -->
                <div class="col-md-4 col-sm-4 col-xs-12">
                    <div class="panel panel-danger">
                        <div class="panel-heading">
                            <i class="fa fa-exclamation-triangle"></i> Alertas Recientes
                        </div>
                        <div class="panel-body">
                            <?php if(empty($alertas_recientes)): ?>
                            <div class="alert alert-success">No hay alertas recientes</div>
                            <?php else: ?>
                            <ul class="list-group">
                                <?php foreach($alertas_recientes as $alerta): ?>
                                <li class="list-group-item">
                                    <span class="badge"><?= substr($alerta['Hora'], 0, 5) ?></span>
                                    <i class="fa fa-warning"></i> <?= htmlspecialchars($alerta['TipoAlerta']) ?>
                                    <?php if($alerta['conductor'] || $alerta['camion']): ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= $alerta['conductor'] ? htmlspecialchars($alerta['conductor']) : '' ?>
                                        <?= $alerta['conductor'] && $alerta['camion'] ? ' - ' : '' ?>
                                        <?= $alerta['camion'] ? htmlspecialchars($alerta['camion']) : '' ?>
                                    </small>
                                    <?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Datos Recientes -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fa fa-history"></i> Registros Recientes - Dispositivo <?= $dispositivo_seleccionado ?>
                        </div>
                        <div class="panel-body">
                            <?php if(empty($registros_recientes)): ?>
                            <div class="alert alert-info">No hay registros recientes para este dispositivo</div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="tabla-registros">
                                    <thead>
                                        <tr>
                                            <th>Fecha/Hora</th>
                                            <th>Frec. Parpadeo (Hz)</th>
                                            <th>Bostezos (x/min)</th>
                                            <th>Posición Cabeza</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($registros_recientes as $registro): ?>
                                        <tr>
                                            <td><?= date('d/m H:i', strtotime($registro['fecha'])) ?></td>
                                            <td><?= number_format($registro['FrecuenciaParpadeo'], 2) ?></td>
                                            <td><?= number_format($registro['FrecuenciaBostezo'], 1) ?></td>
                                            <td><?= $registro['PosicionCabeza'] ?></td>
                                            <td>
                                                <span class="label label-<?= 
                                                    $registro['estado'] == 'Alta' ? 'danger' : 
                                                    ($registro['estado'] == 'Media' ? 'warning' : 'success') 
                                                ?>">
                                                    <?= $registro['estado'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-warning">
                        <h4><i class="fa fa-exclamation-circle"></i> No hay dispositivos activos</h4>
                        <p>Para comenzar a monitorear, active un dispositivo desde la sección <a href="activar.php">ACTIVAR SERVICIO</a>.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTER -->
    <section class="footer-section">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    &copy; 2025 IXITIA | Monitoreo de Conductores
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
    
    <script>
        $(document).ready(function() {
            // Inicializar DataTable
            $('#tabla-registros').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
                }
            });

            // Gráfico solo si hay datos
            <?php if(!empty($datos_grafico)): ?>
            const horas = <?= json_encode(array_column($datos_grafico, 'hora')) ?>;
            const parpadeos = <?= json_encode(array_column($datos_grafico, 'parpadeo')) ?>;
            const bostezos = <?= json_encode(array_column($datos_grafico, 'bostezos')) ?>;
            
            const ctx = document.getElementById('graficoCanvas').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: horas.map(h => h + ':00'),
                    datasets: [
                        {
                            label: 'Frecuencia de Parpadeo (Hz)',
                            data: parpadeos,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            yAxisID: 'y',
                            tension: 0.3
                        },
                        {
                            label: 'Bostezos (x/min)',
                            data: bostezos,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            yAxisID: 'y1',
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Parpadeo (Hz)'
                            },
                            min: 0,
                            max: 1.5
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Bostezos (x/min)'
                            },
                            min: 0,
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>