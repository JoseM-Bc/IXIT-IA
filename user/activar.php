<?php
session_start();
require '../../ogistic/php/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../ogistic/index.html');
    exit;
}

$paso = $_GET['paso'] ?? 1;
$mensaje = '';

// Procesamiento de formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($paso == 1 && isset($_POST['codigo_activacion'])) {
            $_SESSION['codigo_activacion'] = $_POST['codigo_activacion'];
            header('Location: activar.php?paso=2');
            exit;
            
        } elseif ($paso == 2 && isset($_POST['id_dispositivo'])) {
            $_SESSION['id_dispositivo'] = $_POST['id_dispositivo'];
            header('Location: activar.php?paso=3');
            exit;
            
        } elseif ($paso == 3) {
            // Procesar pago con tarjeta (simulado)
            $conn->prepare("INSERT INTO activaciones 
                          (codigo_activacion, id_dispositivo, id_cliente, metodo_pago, monto, estado) 
                          VALUES (?, ?, ?, 'tarjeta', 4999.00, 'activo')")
                 ->execute([
                     $_SESSION['codigo_activacion'],
                     $_SESSION['id_dispositivo'],
                     $_SESSION['user_id']
                 ]);
            
            $mensaje = '¡Pago y activación exitosos!';
            unset($_SESSION['codigo_activacion'], $_SESSION['id_dispositivo']);
        }
    } catch (PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
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
    <!-- [Head igual que antes] -->
    <style>
        .nav-pasos {
            margin-bottom: 20px;
        }
        .nav-pasos .badge {
            margin-right: 5px;
            background-color: #3498db;
        }
        .panel-activacion {
            border-color: #3498db;
        }
        .panel-activacion .panel-heading {
            background-color: #3498db;
            color: white;
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
                    <img src="assets/img/logo2.png" alt="IXITIA" /> <!-- Reemplaza con tu logo -->
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
                            <li><a href="activar.php" class="menu-top-active">ACTIVAR SERVICIO</a></li>
                            <li><a href="seguimiento.php">SEGUIMIENTO</a></li>
                            <li><a href="cuenta.php">MI CUENTA</a></li>
                            <li><a href="accesoAdministrador.php">Administrador de flota</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">ACTIVAR SERVICIO IXITIA</h4>
                    <?php if ($mensaje): ?>
                    <div class="alert alert-success"><?= $mensaje ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pasos de navegación -->
            <ul class="nav nav-pasos">
                <li class="<?= $paso == 1 ? 'active' : '' ?>">
                    <span class="badge">1</span> Código de Activación
                </li>
                <li class="<?= $paso == 2 ? 'active' : '' ?>">
                    <span class="badge">2</span> ID del Dispositivo
                </li>
                <li class="<?= $paso == 3 ? 'active' : '' ?>">
                    <span class="badge">3</span> Pago con Tarjeta
                </li>
            </ul>

            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div class="panel panel-activacion">
                        <div class="panel-heading">
                            <i class="fa fa-plug"></i> 
                            <?= $paso == 1 ? 'Ingresa tu código' : ($paso == 2 ? 'Vincula tu dispositivo' : 'Completa el pago') ?>
                        </div>
                        <div class="panel-body">
                            <?php if ($paso == 1): ?>
                            <!-- Paso 1: Código de activación -->
                            <form method="POST">
                                <div class="form-group">
                                    <label>Código de Activación</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-ticket"></i></span>
                                        <input type="text" name="codigo_activacion" class="form-control" 
                                               placeholder="Ej: IX-2023-ABC123" required>
                                    </div>
                                    <p class="help-block">
                                        <small>Este código viene en tu correo de confirmación de compra.</small>
                                    </p>
                                </div>
                                <button type="submit" class="btn btn-primary">Continuar</button>
                            </form>

                            <?php elseif ($paso == 2): ?>
                            <!-- Paso 2: ID del dispositivo -->
                            <form method="POST">
                                <div class="form-group">
                                    <label>ID del Dispositivo Físico</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-barcode"></i></span>
                                        <input type="text" name="id_dispositivo" class="form-control" 
                                               placeholder="Ej: DSP-1001" required>
                                    </div>
                                    <p class="help-block">
                                        <small>Encuentra este ID en la parte posterior de tu dispositivo IXITIA.</small>
                                    </p>
                                </div>
                                <button type="submit" class="btn btn-primary">Continuar a Pago</button>
                            </form>

                            <?php elseif ($paso == 3): ?>
                            <!-- Paso 3: Pago con tarjeta -->
                            <form method="POST">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <i class="fa fa-credit-card"></i> Información de Pago
                                    </div>
                                    <div class="panel-body">
                                        <!-- Datos de Tarjeta (igual que comprar.php) -->
                                        <div class="form-group">
                                            <label>Número de Tarjeta</label>
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>
                                                <input type="text" class="form-control" 
                                                       placeholder="1234 5678 9012 3456" pattern="\d{16}" 
                                                       title="16 dígitos sin espacios" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Fecha de Expiración</label>
                                                    <input type="text" class="form-control" 
                                                           placeholder="MM/AA" pattern="\d{2}/\d{2}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>CVV</label>
                                                    <input type="password" class="form-control" 
                                                           placeholder="123" pattern="\d{3}" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Nombre en la Tarjeta</label>
                                            <input type="text" class="form-control" 
                                                   placeholder="JUAN PEREZ" required>
                                        </div>

                                        <div class="alert alert-info">
                                            <strong>Total a pagar:</strong> $1,699.00 MXN
                                        </div>

                                        <button type="submit" class="btn btn-success btn-lg btn-block">
                                            <i class="fa fa-lock"></i> CONFIRMAR PAGO Y ACTIVACIÓN
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <?php endif; ?>
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
    <script src="assets/js/custom.js"></script>
    <!-- Ejemplo de gráfico con Chart.js (opcional) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>

