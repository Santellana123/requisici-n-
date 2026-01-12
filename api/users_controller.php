<?php
require '../includes/db_connect.php';
header('Content-Type: application/json');

$op = isset($_GET['op']) ? $_GET['op'] : '';
$method = $_SERVER['REQUEST_METHOD'];

// ---------------------------------------------------------
// 1. LISTAR USUARIOS
// ---------------------------------------------------------
if ($op == 'listar') {
    $sql = "SELECT u.id, u.nombre_usuario, u.rol, u.activo, a.nombre_programa as area 
            FROM usuarios u 
            LEFT JOIN areas a ON u.area_id = a.id 
            ORDER BY u.id ASC";
    $res = $conn->query($sql);
    
    $usuarios = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }
    echo json_encode($usuarios);
    exit;
}

// ---------------------------------------------------------
// 2. OBTENER UN SOLO USUARIO (Para rellenar modal de edición)
// ---------------------------------------------------------
if ($op == 'obtener') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id > 0) {
        $stmt = $conn->prepare("SELECT id, nombre_usuario, rol, area_id, activo FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            echo json_encode($row);
        } else {
            echo json_encode(['error' => 'No encontrado']);
        }
    }
    exit;
}

// ---------------------------------------------------------
// 3. GUARDAR (CREAR O EDITAR)
// ---------------------------------------------------------
if ($op == 'guardar' && $method == 'POST') {
    $id = $_POST['id'] ?? '';
    $usuario = $_POST['nombre_usuario'] ?? '';
    $rol = $_POST['rol'] ?? '';
    $area_id = !empty($_POST['area_id']) ? $_POST['area_id'] : NULL;
    $pass = $_POST['password'] ?? ''; // Solo si es nuevo

    if (empty($id)) {
        // --- CREAR NUEVO ---
        if(empty($pass)) {
            echo json_encode(['success'=>false, 'message'=>'Contraseña requerida']); exit;
        }
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nombre_usuario, password, rol, area_id, activo) VALUES (?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $usuario, $hash, $rol, $area_id);
    } else {
        // --- EDITAR EXISTENTE ---
        $sql = "UPDATE usuarios SET nombre_usuario=?, rol=?, area_id=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $usuario, $rol, $area_id, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Guardado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error BD: ' . $stmt->error]);
    }
    exit;
}

// ---------------------------------------------------------
// 4. CAMBIAR CONTRASEÑA
// ---------------------------------------------------------
if ($op == 'cambiar_password' && $method == 'POST') {
    $id = $_POST['id'];
    $new_pass = $_POST['new_password'];

    if(empty($id) || empty($new_pass)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
        exit;
    }
    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Contraseña actualizada.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error SQL']);
    }
    exit;
}
?>