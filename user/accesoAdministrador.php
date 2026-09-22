<?php
// Incluir la conexión a la base de datos
require_once '../../ogistic/php/db_connect.php';

// Iniciar sesión (debe estar al principio del archivo)
session_start();

// Manejar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    try {
        // Consulta para validar credenciales
        $stmt = $conn->prepare("SELECT * FROM administradorFlota WHERE Correo = :correo AND Contraseña = :contrasena");
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':contrasena', $contrasena);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['admin_flota'] = true;
            $_SESSION['correo_admin'] = $correo;
            header("Location: ../../ogistic/flota/index.php"); 
            exit();
        } else {
            $error = "Credenciales incorrectas";
        }
    } catch(PDOException $e) {
        $error = "Error en la consulta: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="Acceso para administradores de flota" />
    <meta name="author" content="" />
    <title>IXITIA - Admin Flota</title>
    <link rel="icon" href="../../ogistic/images/ixitianuevoSolo.png">
    <!-- BOOTSTRAP CORE STYLE -->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONT AWESOME STYLE -->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLE -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .login-box {
            margin-top: 100px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
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
                            <li><a href="cuenta.php">MI CUENTA</a></li>
                            <li><a href="accesoAdministrador.php"  class="menu-top-active">Administrador de flota</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FORMULARIO DE LOGIN -->
    <div class="container">
        <div class="login-box">
            <div class="login-logo">
                <img src="assets/img/logo2.png" alt="IXITIA" style="max-height: 80px;" />
                <h3>Acceso Administrador de Flota</h3>
            </div>
            
            <form method="POST" action="accesoAdministrador.php">
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                        <input type="email" name="correo" class="form-control" placeholder="admin@flota.com" required />
                    </div>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-key"></i></span>
                        <input type="password" name="contrasena" class="form-control" placeholder="••••••••" required />
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa fa-sign-in"></i> Iniciar Sesión
                    </button>
                </div>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" id="login-error">
                        <i class="fa fa-times-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
</body>
</html>