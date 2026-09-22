<?php
// Incluir archivo de conexión
require_once '../../ogistic/php/db_connect.php';

// Consultas SQL para obtener datos
// 1. Obtener alertas por tipo
$queryAlertas = "SELECT TipoAlerta, COUNT(*) as total FROM alerta GROUP BY TipoAlerta";
$stmtAlertas = $conn->query($queryAlertas);
$alertasData = $stmtAlertas->fetchAll(PDO::FETCH_ASSOC);

// 2. Obtener métricas de dispositivos
$queryMetricas = "SELECT 
                    AVG(FrecuenciaParpadeo) as avg_parpadeo,
                    AVG(FrecuenciaBostezo) as avg_bostezo,
                    COUNT(*) as total_dispositivos,
                    COUNT(DISTINCT id_cliente) as total_clientes
                  FROM dispositivo";
$stmtMetricas = $conn->query($queryMetricas);
$metricasData = $stmtMetricas->fetch(PDO::FETCH_ASSOC);

// 3. Obtener alertas recientes para la tabla
$queryAlertasRecientes = "SELECT a.TipoAlerta, a.Fecha, a.Hora, d.ID_Dispositivo, c.Nombre_Completo as conductor
                          FROM alerta a
                          LEFT JOIN dispositivo d ON a.ID_Dispositivo = d.ID_Dispositivo
                          LEFT JOIN cliente c ON d.id_cliente = c.id
                          ORDER BY a.Fecha DESC, a.Hora DESC
                          LIMIT 10";
$stmtAlertasRecientes = $conn->query($queryAlertasRecientes);
$alertasRecientes = $stmtAlertasRecientes->fetchAll(PDO::FETCH_ASSOC);

// 4. Obtener estadísticas de fatiga
$queryFatiga = "SELECT 
                  COUNT(CASE WHEN FrecuenciaParpadeo > 0.4 THEN 1 END) as alta_frecuencia,
                  COUNT(CASE WHEN FrecuenciaBostezo > 3 THEN 1 END) as alto_bostezo,
                  COUNT(CASE WHEN PosicionCabeza = 'Inclinada' THEN 1 END) as cabeza_inclinada
                FROM dispositivo";
$stmtFatiga = $conn->query($queryFatiga);
$fatigaData = $stmtFatiga->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>IXITIA - Reportes de Flota</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="assets/img/favicon.ico">
    
    <!-- Bootstrap -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Estilos personalizados -->
    <link href="assets/css/templatemo.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        .report-card {
            transition: all 0.3s ease;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .alert-critical {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
        }
        .alert-warning {
            background-color: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
        }
    </style>
</head>

<body>
    <!-- Barra Superior (Contacto) -->
    <nav class="navbar navbar-expand-lg bg-dark navbar-light d-none d-lg-block" id="templatemo_nav_top">
        <div class="container text-light">
            <div class="w-100 d-flex justify-content-between">
                <div>
                    <i class="fa fa-envelope mx-2"></i>
                    <a class="navbar-sm-brand text-light text-decoration-none" href="mailto:flota@ixitia.com">flota@ixitia.com</a>
                    <i class="fa fa-phone mx-2"></i>
                    <a class="navbar-sm-brand text-light text-decoration-none" href="tel:+123456789">+123 456 789</a>
                </div>
                <div>
                    <span class="text-light">Sistema de Gestión de Flota</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header / Barra de Navegación Principal - MODIFICADA -->
    <nav class="navbar navbar-expand-lg navbar-light shadow">
        <div class="container">
            <!-- Logo pegado a la izquierda -->
            <a class="navbar-brand" href="index.html">
                <img src="assets/img/logo2.png" alt="IXITIA Logo" width="173" height="60">
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#admin_main_nav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Contenedor de elementos de navegación pegado a la derecha -->
            <div class="collapse navbar-collapse" id="admin_main_nav">
                <div class="ms-auto d-flex align-items-center">
                    <!-- Menú de navegación -->
                    <ol class="navbar-nav me-4">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="camiones.php">Camiones</a>
                        </li>                        
                        <li class="nav-item">
                            <a class="nav-link" href="conductores.php">Conductores</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="reportes.php">Reportes</a>
                        </li>
                    </ol>
                                        
                    <!-- Botón de cerrar sesión pegado al borde derecho -->
                    <a class="btn btn-outline-danger btn-sm" href="../../ogistic/index.html">
                        <i class="fa fa-fw fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="container-fluid py-5">
        <div class="container">
            <!-- Encabezado y Filtros -->
            <div class="row pb-3">
                <div class="col-md-6">
                    <h2 class="h2">Reportes de Monitoreo de Fatiga</h2>
                    <p class="mb-0">Métricas clave del desempeño de conductores y dispositivos</p>
                </div>
                <div class="col-md-6">
                    <div class="card rounded-0">
                        <div class="card-body py-2">
                            <form class="row g-2" method="GET" action="">
                                <div class="col-md-5">
                                    <input type="date" class="form-control" name="fechaInicio" id="fechaInicio">
                                </div>
                                <div class="col-md-5">
                                    <input type="date" class="form-control" name="fechaFin" id="fechaFin">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fa fa-filter"></i> Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas Resumen -->
            <div class="row mb-4">
                <div class="col-md-3 pb-3">
                    <div class="card report-card h-100 border-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted">Dispositivos Activos</h6>
                                    <h3 class="mb-0"><?php echo $metricasData['total_dispositivos']; ?></h3>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="fa fa-microchip text-primary fa-2x"></i>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-primary">
                                <span class="me-1"><i class="fa fa-users"></i></span>
                                <?php echo $metricasData['total_clientes']; ?> clientes
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 pb-3">
                    <div class="card report-card h-100 border-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted">Alertas Totales</h6>
                                    <h3 class="mb-0"><?php echo array_sum(array_column($alertasData, 'total')); ?></h3>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-3 rounded">
                                    <i class="fa fa-exclamation-triangle text-warning fa-2x"></i>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-warning">
                                <span class="me-1"><i class="fa fa-clock"></i></span>
                                Últimas 24 horas
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 pb-3">
                    <div class="card report-card h-100 border-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted">Frec. Parpadeo</h6>
                                    <h3 class="mb-0"><?php echo round($metricasData['avg_parpadeo'], 2); ?> Hz</h3>
                                </div>
                                <div class="bg-danger bg-opacity-10 p-3 rounded">
                                    <i class="fa fa-eye text-danger fa-2x"></i>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-danger">
                                <span class="me-1"><i class="fa fa-arrow-up"></i></span>
                                <?php echo $fatigaData['alta_frecuencia']; ?> casos críticos
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 pb-3">
                    <div class="card report-card h-100 border-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted">Frec. Bostezos</h6>
                                    <h3 class="mb-0"><?php echo round($metricasData['avg_bostezo'], 2); ?>/h</h3>
                                </div>
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="fa fa-tired text-success fa-2x"></i>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-success">
                                <span class="me-1"><i class="fa fa-arrow-down"></i></span>
                                <?php echo $fatigaData['alto_bostezo']; ?> casos críticos
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row">
                <!-- Gráfico 1: Alertas por Tipo -->
                <div class="col-md-6 pb-4">
                    <div class="card rounded-0 h-100">
                        <div class="card-header bg-dark text-white">
                            <h3 class="h3 mb-0">Distribución de Alertas</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartAlertas"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico 2: Métricas de Fatiga -->
                <div class="col-md-6 pb-4">
                    <div class="card rounded-0 h-100">
                        <div class="card-header bg-dark text-white">
                            <h3 class="h3 mb-0">Indicadores de Fatiga</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartFatiga"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico 3: Tendencias Temporales -->
                <div class="col-md-12 pb-4">
                    <div class="card rounded-0">
                        <div class="card-header bg-dark text-white">
                            <h3 class="h3 mb-0">Tendencias de Fatiga (Últimos 7 días)</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartTendencias"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Alertas Recientes -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card rounded-0">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <h3 class="h3 mb-0">Alertas Recientes</h3>
                            <div>
                                <button class="btn btn-sm btn-outline-light me-2">
                                    <i class="fa fa-file-excel"></i> Exportar
                                </button>
                                <button class="btn btn-sm btn-outline-light">
                                    <i class="fa fa-print"></i> Imprimir
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Dispositivo</th>
                                            <th>Conductor</th>
                                            <th>Tipo Alerta</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Detalles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($alertasRecientes as $alerta): ?>
                                        <tr class="<?php echo $alerta['TipoAlerta'] == 'Distracción' ? 'alert-critical' : 'alert-warning'; ?>">
                                            <td><?php echo $alerta['ID_Dispositivo'] ?? 'N/A'; ?></td>
                                            <td><?php echo $alerta['conductor'] ?? 'No asignado'; ?></td>
                                            <td><?php echo $alerta['TipoAlerta']; ?></td>
                                            <td><?php echo $alerta['Fecha']; ?></td>
                                            <td><?php echo $alerta['Hora']; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary">
                                                    <i class="fa fa-info-circle"></i> Detalles
                                                </button>
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
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark" id="tempaltemo_footer">
        <div class="container">
            <div class="row">
                <div class="col-md-12 pt-5 text-center">
                    <h2 class="h2 text-success border-bottom pb-3 border-light">IXITIA - Sistema de Monitoreo de Fatiga</h2>
                </div>
            </div>

            <div class="w-100 bg-black py-3">
                <div class="container">
                    <div class="row pt-2">
                        <div class="col-12">
                            <p class="text-left text-light">
                                Copyright &copy; <?php echo date('Y'); ?> IXITIA 
                                | Versión 1.0
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/jquery-1.11.0.min.js"></script>
    <script src="assets/js/jquery-migrate-1.2.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Gráficos con Chart.js -->
    <script>
        // Preparar datos para gráficos desde PHP
        const alertasLabels = <?php echo json_encode(array_column($alertasData, 'TipoAlerta')); ?>;
        const alertasValues = <?php echo json_encode(array_column($alertasData, 'total')); ?>;
        
        // Colores para gráficos
        const chartColors = [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)'
        ];

        // Gráfico de Alertas (Doughnut)
        const ctxAlertas = document.getElementById('chartAlertas').getContext('2d');
        new Chart(ctxAlertas, {
            type: 'doughnut',
            data: {
                labels: alertasLabels,
                datasets: [{
                    data: alertasValues,
                    backgroundColor: chartColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Gráfico de Métricas de Fatiga (Bar)
        const ctxFatiga = document.getElementById('chartFatiga').getContext('2d');
        new Chart(ctxFatiga, {
            type: 'bar',
            data: {
                labels: ['Frec. Parpadeo', 'Frec. Bostezos', 'Cabeza Inclinada'],
                datasets: [{
                    label: 'Casos Críticos',
                    data: [
                        <?php echo $fatigaData['alta_frecuencia']; ?>,
                        <?php echo $fatigaData['alto_bostezo']; ?>,
                        <?php echo $fatigaData['cabeza_inclinada']; ?>
                    ],
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(23, 162, 184, 0.7)'
                    ],
                    borderColor: [
                        'rgba(220, 53, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(23, 162, 184, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Tendencias (Line)
        const ctxTendencias = document.getElementById('chartTendencias').getContext('2d');
        new Chart(ctxTendencias, {
            type: 'line',
            data: {
                labels: ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
                datasets: [
                    {
                        label: 'Frecuencia de Parpadeo (Hz)',
                        data: [0.42, 0.38, 0.45, 0.47, 0.51, 0.39, 0.35],
                        borderColor: 'rgba(220, 53, 69, 1)',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Frecuencia de Bostezos (/h)',
                        data: [2.8, 3.1, 3.5, 4.2, 4.8, 3.2, 2.5],
                        borderColor: 'rgba(255, 193, 7, 1)',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>