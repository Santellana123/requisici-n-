<?php
require '../includes/admin_check.php';
require '../includes/db_connect.php';
header('Content-Type: application/json');

try {
    // 1. Obtener el año del parámetro GET, o usar el actual por defecto
    $anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

    // 2. Consulta Preparada filtrando por AÑO
    // Nota: Usamos YEAR(r.fecha_solicitud) para filtrar
    $sql = "SELECT 
                r.id, 
                r.folio, 
                r.fecha_solicitud, 
                r.estado,
                u.nombre_usuario as solicitante,
                a.nombre_programa as nombre_area
            FROM requisiciones r
            JOIN usuarios u ON r.usuario_id = u.id
            JOIN areas a ON r.area_id = a.id
            WHERE YEAR(r.fecha_solicitud) = ? 
            ORDER BY r.fecha_solicitud DESC";

    // Preparamos la sentencia para seguridad
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $anio); // "i" indica que es un número entero
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>