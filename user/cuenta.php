<?php
session_start();
require '../../ogistic/php/db_connect.php'; // Asegúrate que la ruta es correcta

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../ogistic/index.html');
    exit;
}

// Obtener datos del cliente
try {
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        throw new Exception("Usuario no encontrado");
    }
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

// Procesar actualización de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre'])) {
    try {
        $stmt = $conn->prepare("UPDATE cliente SET 
                              Nombre_Completo = :nombre, 
                              Telefono = :telefono, 
                              Dirección = :direccion 
                              WHERE id = :user_id");
        
        $stmt->bindParam(':nombre', $_POST['nombre']);
        $stmt->bindParam(':telefono', $_POST['telefono']);
        $stmt->bindParam(':direccion', $_POST['direccion']);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        
        $stmt->execute();
        
        // Actualizar variable local
        $cliente['Nombre_Completo'] = $_POST['nombre'];
        $cliente['Telefono'] = $_POST['telefono'];
        $cliente['Dirección'] = $_POST['direccion'];
        
        $mensaje_datos = "Datos actualizados correctamente";
    } catch (PDOException $e) {
        $error_datos = "Error al actualizar: " . $e->getMessage();
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_password'])) {
    if (password_verify($_POST['current_password'], $cliente['Contraseña'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            try {
                $new_hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE cliente SET Contraseña = :password WHERE id = :user_id");
                $stmt->bindParam(':password', $new_hashed);
                $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->execute();
                
                $mensaje_password = "Contraseña cambiada. Por favor inicia sesión nuevamente.";
                header("Refresh: 3; url=../../ogistic/index.html");
                session_destroy();
            } catch (PDOException $e) {
                $error_password = "Error al cambiar contraseña: " . $e->getMessage();
            }
        } else {
            $error_password = "Las contraseñas nuevas no coinciden";
        }
    } else {
        $error_password = "Contraseña actual incorrecta";
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="Gestión de cuenta de IXITIA" />
    <meta name="author" content="" />
    <title>IXITIA - Mi Cuenta</title>
    <link rel="icon" href="../../ogistic/images/ixitianuevoSolo.png">
    <!-- BOOTSTRAP CORE STYLE -->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONT AWESOME STYLE -->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLE -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
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
                            <li><a href="seguimiento.php">SEGUIMIENTO</a></li>
                            <li><a href="cuenta.php" class="menu-top-active">MI CUENTA</a></li>
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
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">MI CUENTA</h4>
                </div>
            </div>

            <div class="row">
                <!-- Información Personal -->
                <div class="col-md-6">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <i class="fa fa-user"></i> Datos Personales
                        </div>
                        <div class="panel-body">
                            <?php if (isset($mensaje_datos)): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($mensaje_datos) ?></div>
                            <?php elseif (isset($error_datos)): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error_datos) ?></div>
                            <?php endif; ?>
                            
                            <form id="form-datos" method="POST" action="cuenta.php">
                                <div class="form-group">
                                    <label>Nombre Completo</label>
                                    <input type="text" class="form-control" name="nombre" 
                                           value="<?= htmlspecialchars($cliente['Nombre_Completo'] ?? '') ?>" required />
                                </div>
                                <div class="form-group">
                                    <label>Correo Electrónico</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>" 
                                           readonly style="background-color: #f5f5f5;" />
                                </div>
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="tel" class="form-control" name="telefono" 
                                           value="<?= htmlspecialchars($cliente['Telefono'] ?? '') ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Dirección</label>
                                    <textarea class="form-control" name="direccion" rows="3"><?= htmlspecialchars($cliente['Dirección'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Guardar Cambios
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Cambio de Contraseña -->
                <div class="col-md-6">
                    <div class="panel panel-warning">
                        <div class="panel-heading">
                            <i class="fa fa-lock"></i> Seguridad
                        </div>
                        <div class="panel-body">
                            <?php if (isset($mensaje_password)): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($mensaje_password) ?></div>
                            <?php elseif (isset($error_password)): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error_password) ?></div>
                            <?php endif; ?>
                            
                            <form id="form-password" method="POST" action="cuenta.php">
                                <div class="form-group">
                                    <label>Contraseña Actual</label>
                                    <input type="password" class="form-control" name="current_password" required />
                                </div>
                                <div class="form-group">
                                    <label>Nueva Contraseña</label>
                                    <input type="password" class="form-control" name="new_password" required />
                                </div>
                                <div class="form-group">
                                    <label>Confirmar Contraseña</label>
                                    <input type="password" class="form-control" name="confirm_password" required />
                                </div>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fa fa-key"></i> Cambiar Contraseña
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Dispositivo Vinculado -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fa fa-tablet"></i> Dispositivos Asociados
                        </div>
                        <div class="panel-body">
                            <?php
                            try {
                                $stmt = $conn->prepare("SELECT * FROM dispositivo WHERE id = :user_id");
                                $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                $dispositivo = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($dispositivo): ?>
                                    <p><strong>ID del Dispositivo:</strong> <?= htmlspecialchars($dispositivo['ID_Dispositivo']) ?></p>
                                    <p><strong>Fecha de Activación:</strong> <?= date('d/m/Y', strtotime($dispositivo['Fecha_Activacion'] ?? 'now')) ?></p>
                                    <button class="btn btn-sm btn-danger">
                                        <i class="fa fa-unlink"></i> Desvincular
                                    </button>
                                <?php else: ?>
                                    <p>No hay dispositivos vinculados</p>
                                    <a href="comprar.php" class="btn btn-sm btn-success">
                                        <i class="fa fa-plus"></i> Adquirir dispositivo
                                    </a>
                                <?php endif;
                            } catch (PDOException $e) {
                                echo "<p class='text-danger'>Error al cargar dispositivos</p>";
                            }
                            ?>
                        </div>
                    </div>
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
    <script>
        $(document).ready(function() {
            // Validar que las contraseñas coincidan
            $('#form-password').submit(function(e) {
                if ($('input[name="new_password"]').val() !== $('input[name="confirm_password"]').val()) {
                    e.preventDefault();
                    alert('Las contraseñas nuevas no coinciden');
                    return false;
                }
                return true;
            });
        });
    </script>
</body>
</html>