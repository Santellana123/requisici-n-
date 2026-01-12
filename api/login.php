<?php
session_start();
require '../includes/db_connect.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$usuario = $data['usuario'] ?? '';
$password = $data['password'] ?? '';

$sql = "SELECT u.id, u.nombre_usuario, u.password, u.rol, u.area_id, a.nombre_programa 
        FROM usuarios u 
        LEFT JOIN areas a ON u.area_id = a.id 
        WHERE u.nombre_usuario = ? AND u.activo = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 1) {
    $row = $res->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['nombre'] = $row['nombre_usuario'];
        $_SESSION['rol'] = $row['rol'];
        $_SESSION['area_id'] = $row['area_id'];
        $_SESSION['nombre_area'] = $row['nombre_programa']; // Vital para el POA

        echo json_encode(['status' => 'success', 'rol' => $row['rol']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Contraseña incorrecta']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
}
?>