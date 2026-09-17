<?php
require '../../ogistic/php/db_connect.php';

$idAlerta = $_GET['id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            c.NombreCompleto as Conductor,
            cam.Matricula as Camion,
            d.FrecuenciaParpadeo,
            d.DuracionParpadeos,
            d.PosicionCabeza,
            d.FrecuenciaBostezo,
            d.FrecuenciaPulsaciones
        FROM alerta a
        LEFT JOIN conductor c ON a.ID_Conductor = c.ID_Conductor
        LEFT JOIN camion cam ON a.ID_Camion = cam.ID_Camion
        LEFT JOIN dispositivo d ON a.ID_Dispositivo = d.ID_Dispositivo
        WHERE a.ID_Alerta = :id
    ");
    $stmt->bindParam(':id', $idAlerta, PDO::PARAM_INT);
    $stmt->execute();
    $alerta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($alerta) {
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<h5>Datos en el momento de la alerta:</h5>';
        echo '<ul class="list-group">';
        echo '<li class="list-group-item"><strong>Frecuencia de parpadeo:</strong> '.htmlspecialchars($alerta['FrecuenciaParpadeo'] ?? 'N/A').' Hz</li>';
        echo '<li class="list-group-item"><strong>Duración de parpadeo:</strong> '.htmlspecialchars($alerta['DuracionParpadeos'] ?? 'N/A').' ms</li>';
        echo '<li class="list-group-item"><strong>Posición de cabeza:</strong> '.htmlspecialchars($alerta['PosicionCabeza'] ?? 'N/A').'</li>';
        echo '</ul></div>';
        
        echo '<div class="col-md-6">';
        echo '<h5>Información de la alerta:</h5>';
        echo '<ul class="list-group">';
        echo '<li class="list-group-item"><strong>Tipo:</strong> '.htmlspecialchars($alerta['TipoAlerta'] ?? 'N/A').'</li>';
        echo '<li class="list-group-item"><strong>Fecha:</strong> '.htmlspecialchars($alerta['Fecha'] ?? 'N/A').'</li>';
        echo '<li class="list-group-item"><strong>Hora:</strong> '.htmlspecialchars($alerta['Hora'] ?? 'N/A').'</li>';
        echo '<li class="list-group-item"><strong>Conductor:</strong> '.htmlspecialchars($alerta['Conductor'] ?? 'N/A').'</li>';
        echo '<li class="list-group-item"><strong>Camión:</strong> '.htmlspecialchars($alerta['Camion'] ?? 'N/A').'</li>';
        echo '</ul></div>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-warning">No se encontraron detalles para esta alerta</div>';
    }
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error al cargar detalles: '.htmlspecialchars($e->getMessage()).'</div>';
}
?>