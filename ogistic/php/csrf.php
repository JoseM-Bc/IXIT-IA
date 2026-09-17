<?php
// Verificar si la sesión no está iniciada antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
?>