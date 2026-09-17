<?php
session_start();
require_once 'db_connect.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener ID de alerta
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$alertaId = (int)$_GET['id'];

try {
    $stmt = $conn->prepare("
        SELECT a.*, c.nombre, c.apellido, c.licencia, c.telefono,
               cam.placa, cam.modelo, cam.ultimo_mantenimiento
        FROM alertas a
        LEFT JOIN conductores c ON a.conductor_id = c.id
        LEFT JOIN camiones cam ON a.camion_id = cam.id
        WHERE a.id = ?
    ");
    $stmt->execute([$alertaId]);
    $alerta = $stmt->fetch();

    if (!$alerta) {
        die("Alerta no encontrada");
    }

} catch(PDOException $e) {
    die("Error al obtener detalles: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Detalle de Alerta - IXITIA</title>
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
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-5">
        <div class="container">
            <div class="row pb-3">
                <div class="col-md-12">
                    <h2 class="h2">Detalle de Alerta</h2>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">← Volver al Dashboard</a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card rounded-0">
                        <div class="card-header bg-dark text-white">
                            <h3 class="h3 mb-0">ALT-<?= $alerta['id'] ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Información Básica</h5>
                                    <ul class="list-group list-group-flush mb-3">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span class="fw-bold">Tipo:</span>
                                            <span class="badge bg-<?= 
                                                $alerta['tipo'] == 'Fatiga' ? 'warning text-dark' : 
                                                ($alerta['tipo'] == 'Falla Motor' ? 'danger' : 'secondary') 
                                            ?>">
                                                <?= htmlspecialchars($alerta['tipo']) ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span class="fw-bold">Fecha/Hora:</span>
                                            <span><?= date('d/m/Y H:i', strtotime($alerta['fecha'] . ' ' . $alerta['hora'])) ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span class="fw-bold">Gravedad:</span>
                                            <span><?= htmlspecialchars($alerta['gravedad']) ?></span>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>Vehículo y Conductor</h5>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span class="fw-bold">Camión:</span>
                                            <span><?= htmlspecialchars($alerta['placa'] ?? 'N/A') ?> (<?= htmlspecialchars($alerta['modelo'] ?? 'N/A') ?>)</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span class="fw-bold">Conductor:</span>
                                            <span><?= htmlspecialchars($alerta['nombre'] ?? 'No asignado') ?> <?= htmlspecialchars($alerta['apellido'] ?? '') ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span class="fw-bold">Licencia:</span>
                                            <span><?= htmlspecialchars($alerta['licencia'] ?? 'N/A') ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span class="fw-bold">Teléfono:</span>
                                            <span><?= htmlspecialchars($alerta['telefono'] ?? 'N/A') ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="mt-4">
                                <h5>Detalles Adicionales</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <?= nl2br(htmlspecialchars($alerta['descripcion'] ?? 'Sin detalles adicionales')) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>