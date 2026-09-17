<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validar que el correo tenga @
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Correo electrónico no válido");
    }
    
    // Validar contraseña (aunque ya se validó en el cliente)
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        die("La contraseña debe tener al menos 8 caracteres, una mayúscula y un número");
    }
    
    // Hash de la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Verificar si el correo ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            die("Este correo electrónico ya está registrado");
        }
        
        // Insertar nuevo usuario
        $stmt = $conn->prepare("INSERT INTO usuarios (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $hashed_password]);
        $stmt = $conn->prepare("INSERT INTO cliente (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $hashed_password]);
        
        // Redirigir al login con mensaje de éxito
        header("Location: ../auth/login.html?registered=1");
        exit();
    } catch(PDOException $e) {
        die("Error al registrar el usuario: " . $e->getMessage());
    }
}
?>