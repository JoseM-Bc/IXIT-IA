<?php
require_once 'db_connect.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $stmt = $conn->prepare("SELECT id, password FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Inicio de sesión exitoso
            $_SESSION['user_id'] = $user['id'];
            header("Location: ../user/index.php");
            exit();
        } else {
            // Credenciales incorrectas - Redirigir con parámetro de error
            header("Location: ../auth/login.html?error=1");
            exit();
        }
    } catch(PDOException $e) {
        die("Error al iniciar sesión: " . $e->getMessage());
    }
}
?>
 