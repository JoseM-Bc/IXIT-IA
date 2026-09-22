<?php
session_start();
require '../../ogistic/php/db_connect.php'; // Asegúrate que esta ruta es correcta

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../ogistic/index.html');
    exit;
}

// Procesar el formulario de compra
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Registrar el pago
        $stmt = $conn->prepare("INSERT INTO pago (MétodoPago) VALUES ('tarjeta_credito')");
        $stmt->execute();
        $pago_id = $conn->lastInsertId();

        // 2. Registrar la compra
        $stmt = $conn->prepare("INSERT INTO compra (Fecha, Total, DirecciónEnvio, id, ID_Pago) 
                               VALUES (NOW(), 4999.00, :direccion, :user_id, :pago_id)");
        $stmt->bindParam(':direccion', $_POST['direccion']);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':pago_id', $pago_id, PDO::PARAM_INT);
        $stmt->execute();
        $compra_id = $conn->lastInsertId();

        // 3. Registrar el producto comprado (asumiendo ID_Producto = 1 para el dispositivo)
        $stmt = $conn->prepare("INSERT INTO venta (id, ID_Producto, ID_Compra) 
                               VALUES (:user_id, 1, :compra_id)");
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':compra_id', $compra_id, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['compra_exitosa'] = true;
        // Redirigir a activación con ID de compra
        //header("Location: activar.html?compra_id=$compra_id");
        exit;
    } catch (PDOException $e) {
        die("Error al procesar la compra: " . $e->getMessage());
    }
}

// Obtener datos del cliente para autollenado
try {
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener datos del cliente: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="Compra del dispositivo IXITIA" />
    <meta name="author" content="" />
    <title>IXITIA - Comprar Producto</title>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">    
    <!-- DataTables JS -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
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
    <!-- BARRA SUPERIOR (Igual que en index.html) -->
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

    <!-- MENÚ PRINCIPAL (Igual que en index.html) -->
        <section class="menu-section">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="navbar-collapse collapse">
                        <ul id="menu-top" class="nav navbar-nav navbar-right">
                            <li><a href="index.php">DASHBOARD</a></li>
                            <li><a href="comprar.php " class="menu-top-active">Adquiere tu producto</a></li>
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
            <!-- Título -->
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">COMPRAR DISPOSITIVO IXITIA</h4>
                </div>
            </div>

            <!-- Información del Producto -->
            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <i class="fa fa-cube"></i> Detalles del Producto
                        </div>
                        <div class="panel-body">
                            <div class="text-center">
                                <img src="assets/img/DispositivoIxitia1.png" alt="Dispositivo IXITIA" class="img-responsive" style="max-height: 200px; margin: 0 auto;" />
                                <h3>Dispositivo IXITIA</h3>
                                <p class="lead text-success">$4,999.00 MXN</p>
                            </div>
                            <ul class="list-group">
                                <li class="list-group-item"><i class="fa fa-check"></i> Monitoreo en tiempo real</li>
                                <li class="list-group-item"><i class="fa fa-check"></i> Alertas de fatiga</li>
                                <li class="list-group-item"><i class="fa fa-check"></i> Soporte 24/7</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Pago -->
                <div class="col-md-6">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-credit-card"></i> Información de Pago
                        </div>
                        <div class="panel-body">
                            <form id="form-pago" method="POST" action="comprar.php">
                                <!-- Datos de Tarjeta -->
                                <div class="form-group">
                                    <label>Número de Tarjeta</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>
                                        <input type="text" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Fecha de Expiración</label>
                                            <input type="text" class="form-control" placeholder="MM/AA" maxlength="5" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>CVV</label>
                                            <input type="password" class="form-control" placeholder="123" maxlength="3" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Nombre en la Tarjeta</label>
                                    <input type="text" class="form-control" placeholder="" />
                                </div>

                                <hr />

                                <!-- Dirección de Envío -->
                                <h5><i class="fa fa-truck"></i> Dirección de Envío</h5>
                                <div class="form-group">
                                    <label>Calle y Número</label>
                                    <input type="text" class="form-control" name="direccion" required />
                                </div>    
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Ciudad</label>
                                            <input type="text" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Código Postal</label>
                                            <input type="text" class="form-control" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Botón de Confirmación -->
                                <button type="submit" class="btn btn-success btn-lg btn-block">
                                    <i class="fa fa-lock"></i> CONFIRMAR COMPRA
                                </button>

                                <div class="alert alert-info" style="margin-top: 15px;">
                                    <small>
                                        <i class="fa fa-info-circle"></i> Este es un simulador. No se guardarán datos reales de pago.
                                    </small>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER (Igual que en index.html) -->
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
    <script>
        // Simulador de pago (sin envío real)
        $(document).ready(function() {
            $('#form-pago').submit(function(e) {
                e.preventDefault();
                
                // Simular validación
                if ($('input[name="direccion"]').val() === '') {
                    alert('Por favor ingresa tu dirección');
                    return;
                }
                
                // Enviar formulario REAL
                this.submit();
            });
        });
    </script>
</body>
</html>




