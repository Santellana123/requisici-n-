<?php
require '../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

// Verificar permisos
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] != 'director_planeacion' && $_SESSION['rol'] != 'admin')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Recibir datos (esta vez solo esperamos el ID)
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Falta el ID']);
    exit;
}

try {
    // Actualizamos directo a 'aprobado_direccion' (que equivale a Realizada/Aprobada)
    $sql = "UPDATE requisiciones 
            SET estado = 'aprobado_direccion', 
                aprobado_por = ?, 
                fecha_aprobacion_direccion = NOW()
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $_SESSION['user_id'], $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Requisición marcada como realizada.']);
    } else {
        throw new Exception("Error en la base de datos.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>