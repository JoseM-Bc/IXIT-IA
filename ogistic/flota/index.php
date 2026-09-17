<?php
// Incluir conexión a la base de datos
require_once '../../ogistic/php/db_connect.php';

// Consultas para obtener datos estadísticos
$query_alertas = "SELECT COUNT(*) as total FROM alerta WHERE DATE(Fecha) = CURDATE()";
$query_conductores = "SELECT COUNT(*) as total FROM conductor";
$query_camiones = "SELECT COUNT(*) as total FROM camion";
$query_dispositivos = "SELECT COUNT(*) as total FROM dispositivo WHERE id_cliente IS NOT NULL";

// Ejecutar consultas
$alertas_hoy = $conn->query($query_alertas)->fetch(PDO::FETCH_ASSOC);
$total_conductores = $conn->query($query_conductores)->fetch(PDO::FETCH_ASSOC);
$total_camiones = $conn->query($query_camiones)->fetch(PDO::FETCH_ASSOC);
$total_dispositivos = $conn->query($query_dispositivos)->fetch(PDO::FETCH_ASSOC);

// Consulta para alertas recientes (últimas 5)
$query_alertas_recientes = "SELECT a.ID_Alerta, a.TipoAlerta, a.Fecha, a.Hora, 
                           c.NombreCompleto as Conductor, cam.Matricula as Camion
                           FROM alerta a
                           LEFT JOIN conductor c ON a.ID_Conductor = c.ID_Conductor
                           LEFT JOIN camion cam ON a.ID_Camion = cam.ID_Camion
                           ORDER BY a.Fecha DESC, a.Hora DESC LIMIT 5";
$alertas_recientes = $conn->query($query_alertas_recientes)->fetchAll(PDO::FETCH_ASSOC);

// Consulta para gráfico de tipos de alertas
$query_tipos_alertas = "SELECT TipoAlerta, COUNT(*) as cantidad 
                        FROM alerta 
                        WHERE Fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                        GROUP BY TipoAlerta";
$tipos_alertas = $conn->query($query_tipos_alertas)->fetchAll(PDO::FETCH_ASSOC);

// Preparar datos para el gráfico
$labels = [];
$data = [];
foreach ($tipos_alertas as $alerta) {
    $labels[] = $alerta['TipoAlerta'];
    $data[] = $alerta['cantidad'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>IXITIA - Admin Flota</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="assets/img/favicon.ico">
    
    <!-- Bootstrap -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link href="assets/css/templatemo.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
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

    <!-- Header / Barra de Navegación Principal -->
    <nav class="navbar navbar-expand-lg navbar-light shadow">
        <div class="container">
            <!-- Logo pegado a la izquierda -->
            <a class="navbar-brand" href="index.php">
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
                            <a class="nav-link active" href="index.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="camiones.php">Camiones</a>
                        </li>                        
                        <li class="nav-item">
                            <a class="nav-link" href="conductores.php">Conductores</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reportes.php">Reportes</a>
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
            <!-- Encabezado -->
            <div class="row pb-3">
                <div class="col-md-12">
                    <h2 class="h2">Panel de Control</h2>
                    <p class="mb-0">Resumen operativo de la flota - <?php echo date('d/m/Y'); ?></p>
                </div>
            </div>

            <!-- Tarjetas de Resumen -->
            <div class="row">
                <!-- Tarjeta 1: Camiones Activos -->
                <div class="col-md-3 pb-5">
                    <div class="card bg-primary text-white h-100 rounded-0">
                        <div class="card-body text-center">
                            <h3 class="card-title h1"><?php echo $total_camiones['total']; ?></h3>
                            <p class="card-text">Camiones Registrados</p>
                            <i class="fa fa-truck fa-3x mt-3"></i>
                        </div>
                        <div class="card-footer bg-primary-dark">
                            <a href="camiones.php" class="text-white text-decoration-none">Ver todos <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta 2: Alertas Hoy -->
                <div class="col-md-3 pb-5">
                    <div class="card bg-warning text-dark h-100 rounded-0">
                        <div class="card-body text-center">
                            <h3 class="card-title h1"><?php echo $alertas_hoy['total']; ?></h3>
                            <p class="card-text">Alertas Hoy</p>
                            <i class="fa fa-exclamation-triangle fa-3x mt-3"></i>
                        </div>
                        <div class="card-footer bg-warning-dark">
                            <a href="reportes.php" class="text-dark text-decoration-none">Ver reportes <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta 3: Conductores -->
                <div class="col-md-3 pb-5">
                    <div class="card bg-success text-white h-100 rounded-0">
                        <div class="card-body text-center">
                            <h3 class="card-title h1"><?php echo $total_conductores['total']; ?></h3>
                            <p class="card-text">Conductores Activos</p>
                            <i class="fa fa-users fa-3x mt-3"></i>
                        </div>
                        <div class="card-footer bg-success-dark">
                            <a href="conductores.php" class="text-white text-decoration-none">Ver conductores <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta 4: Dispositivos -->
                <div class="col-md-3 pb-5">
                    <div class="card bg-info text-white h-100 rounded-0">
                        <div class="card-body text-center">
                            <h3 class="card-title h1"><?php echo $total_dispositivos['total']; ?></h3>
                            <p class="card-text">Dispositivos Activos</p>
                            <i class="fa fa-microchip fa-3x mt-3"></i>
                        </div>
                        <div class="card-footer bg-info-dark">
                            <a href="reportes.php" class="text-white text-decoration-none">Ver detalles <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Gráficos y Alertas -->
            <div class="row">
                <!-- Gráfico de Tipos de Alertas -->
                <div class="col-md-6 pb-4">
                    <div class="card rounded-0 h-100">
                        <div class="card-header bg-dark text-white">
                            <h3 class="h3 mb-0">Distribución de Alertas (Últimos 7 días)</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="alertasChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Alertas Recientes -->
                <div class="col-md-6 pb-4">
                    <div class="card rounded-0 h-100">
                        <div class="card-header bg-dark text-white">
                            <h3 class="h3 mb-0">Alertas Recientes</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tipo</th>
                                            <th>Conductor</th>
                                            <th>Camion</th>
                                            <th>Fecha/Hora</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($alertas_recientes) > 0): ?>
                                            <?php foreach ($alertas_recientes as $alerta): ?>
                                                <tr>
                                                    <td>ALT-<?php echo $alerta['ID_Alerta']; ?></td>
                                                    <td>
                                                        <span class="badge 
                                                            <?php echo $alerta['TipoAlerta'] == 'Fatiga' ? 'bg-warning text-dark' : 'bg-danger'; ?>">
                                                            <?php echo $alerta['TipoAlerta']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $alerta['Conductor'] ?? 'N/A'; ?></td>
                                                    <td><?php echo $alerta['Camion'] ?? 'N/A'; ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($alerta['Fecha'] . ' ' . $alerta['Hora'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No hay alertas recientes</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <a href="reportes.php" class="btn btn-sm btn-dark">Ver todas las alertas</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Resumen de Dispositivos -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card rounded-0">
                        <div class="card-header bg-dark text-white">
                            <h3 class="h3 mb-0">Estado de Dispositivos</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success rounded-circle p-3 me-3">
                                            <i class="fa fa-check fa-2x text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo $total_dispositivos['total']; ?></h4>
                                            <p class="mb-0">Dispositivos activos</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-warning rounded-circle p-3 me-3">
                                            <i class="fa fa-exclamation fa-2x text-dark"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php 
                                                $query_pendientes = "SELECT COUNT(*) as total FROM dispositivo WHERE id_cliente IS NULL";
                                                $pendientes = $conn->query($query_pendientes)->fetch(PDO::FETCH_ASSOC);
                                                echo $pendientes['total'];
                                            ?></h4>
                                            <p class="mb-0">Dispositivos sin asignar</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-danger rounded-circle p-3 me-3">
                                            <i class="fa fa-times fa-2x text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php 
                                                $query_inactivos = "SELECT COUNT(*) as total FROM activaciones WHERE estado = 'vencido'";
                                                $inactivos = $conn->query($query_inactivos)->fetch(PDO::FETCH_ASSOC);
                                                echo $inactivos['total'];
                                            ?></h4>
                                            <p class="mb-0">Dispositivos inactivos</p>
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

    <!-- Footer -->
    <footer class="bg-dark" id="tempaltemo_footer">
        <div class="container">
            <div class="row">
                <div class="col-md-12 pt-5 text-center">
                    <h2 class="h2 text-success border-bottom pb-3 border-light">IXITIA - Gestión de Flota</h2>                   
                </div>
            </div>

            <div class="w-100 bg-black py-3">
                <div class="container">
                    <div class="row pt-2">
                        <div class="col-12">
                            <p class="text-left text-light">
                                Copyright &copy; <?php echo date('Y'); ?> IXITIA 
                                | Versión 1.0 | Última actualización: <?php echo date('d/m/Y H:i'); ?>
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
    <script src="assets/js/custom.js"></script>
    
    <!-- Script para el gráfico -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('alertasChart').getContext('2d');
            const alertasChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($data); ?>,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>