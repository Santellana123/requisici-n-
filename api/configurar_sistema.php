<?php
require '../includes/db_connect.php'; // Tu conexión MySQLi
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// --- GET: Obtener el año activo ---
if ($method == 'GET') {
    $sql = "SELECT valor FROM configuracion WHERE clave = 'anio_fiscal_activo' LIMIT 1";
    $res = $conn->query($sql);
    
    if ($row = $res->fetch_assoc()) {
        echo json_encode(['anio' => $row['valor']]);
    } else {
        // Si no existe, devolvemos el año actual del servidor
        echo json_encode(['anio' => date('Y')]);
    }
    exit;
}

// --- POST: Guardar el año activo (Solo Admins) ---
if ($method == 'POST') {
    require '../includes/admin_check.php'; // Seguridad extra (opcional si ya controlas sesión antes)
    
    $input = json_decode(file_get_contents('php://input'), true);
    $anio = intval($input['anio']);

    if ($anio < 2020 || $anio > 2050) {
        echo json_encode(['success' => false, 'message' => "Año inválido"]);
        exit;
    }

    // Usamos ON DUPLICATE KEY UPDATE por si la fila no existe
    $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor) VALUES ('anio_fiscal_activo', ?) ON DUPLICATE KEY UPDATE valor = ?");
    $stmt->bind_param("ss", $anio, $anio);

    if($stmt->execute()){
        echo json_encode(['success' => true, 'message' => "Sistema configurado al año $anio"]);
    } else {
        echo json_encode(['success' => false, 'message' => "Error BD: " . $conn->error]);
    }
    exit;
}
?>