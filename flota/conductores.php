<?php
// Conexión a la base de datos
require_once '../../ogistic/php/db_connect.php';

// Procesar operaciones CRUD
$mensaje = '';
$error = '';

// Crear conductor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_conductor'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO conductor (NombreCompleto, Edad, Sexo, Telefono) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_POST['nombre_completo'],
            $_POST['edad'],
            $_POST['sexo'],
            $_POST['telefono']
        ]);
        $mensaje = "Conductor creado exitosamente";
    } catch(PDOException $e) {
        $error = "Error al crear conductor: " . $e->getMessage();
    }
}

// Actualizar conductor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_conductor'])) {
    try {
        $stmt = $conn->prepare("UPDATE conductor SET NombreCompleto=?, Edad=?, Sexo=?, Telefono=? WHERE ID_Conductor=?");
        $stmt->execute([
            $_POST['nombre_completo'],
            $_POST['edad'],
            $_POST['sexo'],
            $_POST['telefono'],
            $_POST['id_conductor']
        ]);
        $mensaje = "Conductor actualizado exitosamente";
    } catch(PDOException $e) {
        $error = "Error al actualizar conductor: " . $e->getMessage();
    }
}

// Eliminar conductor
if (isset($_GET['eliminar'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM conductor WHERE ID_Conductor=?");
        $stmt->execute([$_GET['eliminar']]);
        $mensaje = "Conductor eliminado exitosamente";
    } catch(PDOException $e) {
        $error = "Error al eliminar conductor: " . $e->getMessage();
    }
}

// Obtener lista de conductores
$conductores = [];
try {
    $stmt = $conn->query("SELECT * FROM conductor");
    $conductores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error al obtener conductores: " . $e->getMessage();
}

// Obtener conductor para editar
$conductor_editar = null;
if (isset($_GET['editar'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM conductor WHERE ID_Conductor=?");
        $stmt->execute([$_GET['editar']]);
        $conductor_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error al obtener conductor: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>IXITIA - Gestión de Conductores</title>
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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        .driver-photo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #dee2e6;
        }
        .license-badge {
            font-size: 0.75rem;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .active-dot {
            background-color: #28a745;
        }
        .inactive-dot {
            background-color: #dc3545;
        }
        .alert-fixed {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            width: 300px;
        }
    </style>
</head>

<body>
    <!-- Mostrar mensajes -->
    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show alert-fixed">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show alert-fixed">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

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
            <a class="navbar-brand" href="index.html">
                <img src="assets/img/logo2.png" alt="IXITIA Logo" width="173" height="60">
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#admin_main_nav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="admin_main_nav">
                <div class="ms-auto d-flex align-items-center">
                    <ol class="navbar-nav me-4">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="camiones.php">Camiones</a>
                        </li>                        
                        <li class="nav-item">
                            <a class="nav-link active" href="conductores.php">Conductores</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reportes.php">Reportes</a>
                        </li>
                    </ol>
                    
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
            <!-- Encabezado y Botones -->
            <div class="row pb-3">
                <div class="col-md-6">
                    <h2 class="h2">Gestión de Conductores</h2>
                    <p class="mb-0">Registro y asignación de operadores</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#modalAgregarConductor">
                        <i class="fa fa-plus"></i> Nuevo Conductor
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card rounded-0">
                        <div class="card-body py-3">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" name="busqueda" class="form-control" placeholder="Nombre o ID" value="<?= $_GET['busqueda'] ?? '' ?>">
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="sexo">
                                        <option value="">Género (Todos)</option>
                                        <option value="Masculino" <?= (isset($_GET['sexo']) && $_GET['sexo'] == 'Masculino' ? 'selected' : '') ?>>Masculino</option>
                                        <option value="Femenino" <?= (isset($_GET['sexo']) && $_GET['sexo'] == 'Femenino' ? 'selected' : '') ?>>Femenino</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fa fa-search"></i> Buscar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Conductores -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card rounded-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="bg-dark text-white">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Edad</th>
                                            <th>Género</th>
                                            <th>Teléfono</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($conductores)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No hay conductores registrados</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($conductores as $conductor): ?>
                                                <tr>
                                                    <td><?= $conductor['ID_Conductor'] ?></td>
                                                    <td><?= htmlspecialchars($conductor['NombreCompleto']) ?></td>
                                                    <td><?= $conductor['Edad'] ?? 'N/A' ?></td>
                                                    <td><?= $conductor['Sexo'] ?? 'N/A' ?></td>
                                                    <td><?= $conductor['Telefono'] ?? 'N/A' ?></td>
                                                    <td>
                                                        <a href="conductores.php?editar=<?= $conductor['ID_Conductor'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <a href="conductores.php?eliminar=<?= $conductor['ID_Conductor'] ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este conductor?')">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar/Editar Conductor -->
    <div class="modal fade" id="modalAgregarConductor" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalLabel"><?= isset($conductor_editar) ? 'Editar' : 'Registrar' ?> Conductor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <?php if (isset($conductor_editar)): ?>
                            <input type="hidden" name="id_conductor" value="<?= $conductor_editar['ID_Conductor'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo*</label>
                            <input type="text" class="form-control" name="nombre_completo" required 
                                   value="<?= isset($conductor_editar) ? htmlspecialchars($conductor_editar['NombreCompleto']) : '' ?>">
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Edad</label>
                                <input type="number" class="form-control" name="edad" 
                                       value="<?= isset($conductor_editar) ? $conductor_editar['Edad'] : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Género</label>
                                <select class="form-select" name="sexo">
                                    <option value="">Seleccionar...</option>
                                    <option value="Masculino" <?= (isset($conductor_editar) && $conductor_editar['Sexo'] == 'Masculino' ? 'selected' : '') ?>>Masculino</option>
                                    <option value="Femenino" <?= (isset($conductor_editar) && $conductor_editar['Sexo'] == 'Femenino' ? 'selected' : '') ?>>Femenino</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" name="telefono" 
                                   value="<?= isset($conductor_editar) ? $conductor_editar['Telefono'] : '' ?>">
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success" name="<?= isset($conductor_editar) ? 'actualizar_conductor' : 'crear_conductor' ?>">
                                <?= isset($conductor_editar) ? 'Actualizar' : 'Guardar' ?> Conductor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark" id="tempaltemo_footer">
        <div class="container">
            <div class="row">
                <div class="col-md-12 pt-5 text-center">
                    <h2 class="h2 text-success border-bottom pb-3 border-light">IXITIA - Sistema de Gestión de Flota</h2>
                </div>
            </div>

            <div class="w-100 bg-black py-3">
                <div class="container">
                    <div class="row pt-2">
                        <div class="col-12">
                            <p class="text-left text-light">
                                Copyright &copy; 2023 IXITIA 
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
    
    <script>
        // Mostrar modal automáticamente si hay un conductor para editar
        <?php if (isset($conductor_editar)): ?>
            $(document).ready(function() {
                $('#modalAgregarConductor').modal('show');
            });
        <?php endif; ?>
        
        // Cerrar mensajes después de 5 segundos
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    </script>
</body>
</html>