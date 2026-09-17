<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    
    try {
        // Verificar si el correo existe
        $stmt = $conn->prepare("SELECT id FROM cliente WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generar token y fecha de expiración (1 hora)
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));
            
            // Guardar token en la base de datos
            $stmt = $conn->prepare("UPDATE cliente SET reset_token = ?, token_expiry = ? WHERE id = ?");
            $stmt->execute([$token, $expiry, $user['id']]);
            
            // En un entorno real, aquí enviarías un correo con el enlace para restablecer
            // Por ahora solo mostramos el enlace (en producción nunca hagas esto)
            $reset_link = "http://localhost/proyecto_login/auth/reset_password.html?token=$token";
            echo "Se ha enviado un enlace de recuperación a tu correo. (En desarrollo: <a href='$reset_link'>$reset_link</a>)";
        } else {
            echo "Si el correo existe, se ha enviado un enlace de recuperación.";
            // No revelamos si el correo existe o no por seguridad
        }
    } catch(PDOException $e) {
        die("Error al procesar la recuperación: " . $e->getMessage());
    }
}
?>