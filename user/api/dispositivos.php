<?php
// Encabezados para API REST
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // Permite cualquier origen (en producción restringe esto)
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Métodos permitidos
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Headers permitidos

// Incluir conexión a BD
require_once __DIR__ . '/../../../ogistic/php/db_connect.php';

// Manejar preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo aceptamos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Leer el input JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validar datos básicos
if (empty($input) || 
    !isset($input['id_dispositivo']) || 
    !isset($input['frecuencia_parpadeo']) || 
    !isset($input['frecuencia_bostezo'])) {
    
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

try {
    // Preparar consulta SQL
    $stmt = $conn->prepare("
        INSERT INTO dispositivo 
        (ID_Dispositivo, FrecuenciaParpadeo, FrecuenciaBostezo, PosicionCabeza, fecha_registro)
        VALUES (:id, :parpadeo, :bostezo, :posicion, NOW())
    ");
    
    // Ejecutar con parámetros nombrados (más seguro)
    $stmt->execute([
        ':id' => $input['id_dispositivo'],
        ':parpadeo' => floatval($input['frecuencia_parpadeo']),
        ':bostezo' => floatval($input['frecuencia_bostezo']),
        ':posicion' => $input['posicion_cabeza'] ?? 'Centro' // Valor por defecto
    ]);
    
    // Respuesta exitosa
    http_response_code(201); // Creado
    echo json_encode([
        'success' => true,
        'message' => 'Datos registrados',
        'dispositivo' => $input['id_dispositivo'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    // Error de base de datos
    http_response_code(500); // Error interno
    echo json_encode([
        'error' => 'Error en la base de datos',
        'details' => $e->getMessage()
    ]);
}