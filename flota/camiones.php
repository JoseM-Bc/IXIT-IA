<?php
// Incluir archivo de conexión
require_once '../../ogistic/php/db_connect.php';

// Procesar operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Agregar nuevo camión
    if (isset($_POST['agregar_camion'])) {
        $matricula = $_POST['matricula'];
        $modelo = $_POST['modelo'];
        $anio = $_POST['anio'];
        $capacidad = $_POST['capacidad'];        
        
        try {
            $stmt = $conn->prepare("INSERT INTO camion (Matricula, Modelo, Año, Capacidad_de_carga) 
                                   VALUES (:matricula, :modelo, :anio, :capacidad)");
            $stmt->bindParam(':matricula', $matricula);
            $stmt->bindParam(':modelo', $modelo);
            $stmt->bindParam(':anio', $anio);
            $stmt->bindParam(':capacidad', $capacidad);
            $stmt->execute();
            
            $_SESSION['mensaje'] = "Camión agregado correctamente";
            $_SESSION['tipo_mensaje'] = "success";
        } catch(PDOException $e) {
            $_SESSION['mensaje'] = "Error al agregar camión: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "danger";
        }
    }
    
    // Actualizar camión
    if (isset($_POST['actualizar_camion'])) {
        $id = $_POST['id_camion'];
        $matricula = $_POST['matricula'];
        $modelo = $_POST['modelo'];
        $anio = $_POST['anio'];
        $capacidad = $_POST['capacidad'];
        
        try {
            $stmt = $conn->prepare("UPDATE camion 
                                  SET Matricula = :matricula, Modelo = :modelo, 
                                      Año = :anio, Capacidad_de_carga = :capacidad 
                                  WHERE ID_Camion = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':matricula', $matricula);
            $stmt->bindParam(':modelo', $modelo);
            $stmt->bindParam(':anio', $anio);
            $stmt->bindParam(':capacidad', $capacidad);
            $stmt->execute();
            
            $_SESSION['mensaje'] = "Camión actualizado correctamente";
            $_SESSION['tipo_mensaje'] = "success";
        } catch(PDOException $e) {
            $_SESSION['mensaje'] = "Error al actualizar camión: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "danger";
        }
    }
    
    // Eliminar camión
    if (isset($_POST['eliminar_camion'])) {
        $id = $_POST['id_camion'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM camion WHERE ID_Camion = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $_SESSION['mensaje'] = "Camión eliminado correctamente";
            $_SESSION['tipo_mensaje'] = "success";
        } catch(PDOException $e) {
            $_SESSION['mensaje'] = "Error al eliminar camión: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "danger";
        }
    }
}

// Obtener lista de camiones
$camiones = [];
try {
    $stmt = $conn->query("SELECT * FROM camion ORDER BY Matricula");
    $camiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['mensaje'] = "Error al cargar camiones: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "danger";
}

// Mostrar mensajes
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = $_SESSION['tipo_mensaje'];
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>IXITIA - Gestión de Camiones</title>
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
                            <a class="nav-link active" href="camiones.php">Camiones</a>
                        </li>                        
                        <li class="nav-item">
                            <a class="nav-link" href="conductores.php">Conductores</a>
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
            <!-- Mostrar mensajes -->
            <?php if (isset($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Encabezado y Botón de Agregar -->
            <div class="row pb-3">
                <div class="col-md-6">
                    <h2 class="h2">Gestión de Camiones</h2>
                    <p class="mb-0">Registro y seguimiento de la flota vehicular</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregarCamion">
                        <i class="fa fa-plus"></i> Agregar Camión
                    </button>
                </div>
            </div>

            <!-- Tabla de Camiones -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card rounded-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="bg-dark text-white">
                                        <tr>
                                            <th>ID</th>
                                            <th>Matrícula</th>
                                            <th>Modelo</th>
                                            <th>Año</th>
                                            <th>Capacidad (kg)</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($camiones)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No hay camiones registrados</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($camiones as $camion): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($camion['ID_Camion']); ?></td>
                                                    <td><?php echo htmlspecialchars($camion['Matricula']); ?></td>
                                                    <td><?php echo htmlspecialchars($camion['Modelo']); ?></td>
                                                    <td><?php echo htmlspecialchars($camion['Año']); ?></td>
                                                    <td><?php echo number_format($camion['Capacidad_de_carga'], 0); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#modalEditarCamion"
                                                                data-id="<?php echo $camion['ID_Camion']; ?>"
                                                                data-matricula="<?php echo $camion['Matricula']; ?>"
                                                                data-modelo="<?php echo $camion['Modelo']; ?>"
                                                                data-anio="<?php echo $camion['Año']; ?>"
                                                                data-capacidad="<?php echo $camion['Capacidad_de_carga']; ?>">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <form method="POST" action="camiones.php" style="display: inline;">
                                                            <input type="hidden" name="id_camion" value="<?php echo $camion['ID_Camion']; ?>">
                                                            <button type="submit" name="eliminar_camion" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="return confirm('¿Estás seguro de eliminar este camión?')">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
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

    <!-- Modal para Agregar Camión -->
    <div class="modal fade" id="modalAgregarCamion" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalLabel">Registrar Nuevo Camión</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="camiones.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Matrícula*</label>
                            <input type="text" class="form-control" name="matricula" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Modelo*</label>
                            <input type="text" class="form-control" name="modelo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Año*</label>
                            <input type="number" class="form-control" name="anio" min="2000" max="<?php echo date('Y'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacidad (kg)*</label>
                            <input type="number" class="form-control" name="capacidad" step="0.01" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="agregar_camion" class="btn btn-success">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Camión -->
    <div class="modal fade" id="modalEditarCamion" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalEditarLabel">Editar Camión</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="camiones.php">
                    <input type="hidden" name="id_camion" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Matrícula*</label>
                            <input type="text" class="form-control" name="matricula" id="edit_matricula" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Modelo*</label>
                            <input type="text" class="form-control" name="modelo" id="edit_modelo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Año*</label>
                            <input type="number" class="form-control" name="anio" id="edit_anio" min="2000" max="<?php echo date('Y'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacidad (kg)*</label>
                            <input type="number" class="form-control" name="capacidad" id="edit_capacidad" step="0.01" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="actualizar_camion" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
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
    <script>
        // Script para cargar datos en el modal de edición
        $(document).ready(function(){
            $('#modalEditarCamion').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var matricula = button.data('matricula');
                var modelo = button.data('modelo');
                var anio = button.data('anio');
                var capacidad = button.data('capacidad');
                
                var modal = $(this);
                modal.find('#edit_id').val(id);
                modal.find('#edit_matricula').val(matricula);
                modal.find('#edit_modelo').val(modelo);
                modal.find('#edit_anio').val(anio);
                modal.find('#edit_capacidad').val(capacidad);
            });
        });
    </script>
</body>
</html>