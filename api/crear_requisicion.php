<?php
session_start();
require '../includes/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión expirada']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$area_id = $_SESSION['area_id'];

if (empty($data['items'])) {
    echo json_encode(['status' => 'error', 'message' => 'El carrito está vacío']);
    exit;
}

// Agrupar items repetidos
$items_procesados = [];
foreach ($data['items'] as $item) {
    $id = $item['id'];
    if (isset($items_procesados[$id])) {
        $items_procesados[$id]['cantidad'] += $item['cantidad'];
    } else {
        $items_procesados[$id] = $item;
    }
}

$conn->begin_transaction();

try {
    
    // 1. INSERTAR ENCABEZADO
    // El folio se genera automático por el Trigger en la BD, no lo enviamos aquí.
    $sql = "INSERT INTO requisiciones 
            (usuario_id, area_id, fecha_solicitud, motivo_solicitud, programa_poa, proyecto_poa, observaciones_usuario, estado, fecha_creacion) 
            VALUES (?, ?, NOW(), ?, ?, ?, ?, 'pendiente', NOW())";
            
    $stmt = $conn->prepare($sql);
    
    $motivo = isset($data['motivo']) ? $data['motivo'] : 'Sin motivo';
    $programa = isset($data['programa']) ? $data['programa'] : '';
    $proyecto = isset($data['proyecto']) ? $data['proyecto'] : '';
    $obs = isset($data['observaciones']) ? $data['observaciones'] : '';

    $stmt->bind_param("iissss", $user_id, $area_id, $motivo, $programa, $proyecto, $obs);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al crear encabezado: " . $stmt->error);
    }
    
    $req_id = $conn->insert_id;

    // 2. PREPARAR INSERCIÓN DE DETALLES
    // CORRECCIÓN 1: La tabla correcta en tu SQL es 'requisicion_detalles'
    $sql_item = "INSERT INTO requisicion_detalles 
                 (requisicion_id, poa_item_id, cantidad_solicitada, precio_unitario_aplicado) 
                 VALUES (?, ?, ?, ?)";
    $stmt_item = $conn->prepare($sql_item);

    // CORRECCIÓN 2: Eliminamos la sentencia UPDATE poa_items... 
    // porque el TRIGGER de la base de datos ya hace el descuento.

    foreach($items_procesados as $item) {
        $poa_id = $item['id'];
        $cantidad = $item['cantidad'];

        // Validar Stock antes de insertar (bloqueo de fila para evitar condiciones de carrera)
        $query_stock = "SELECT cantidad_disponible, precio_unitario FROM poa_items WHERE id = $poa_id FOR UPDATE";
        $chk = $conn->query($query_stock)->fetch_assoc();
        
        if (!$chk || $chk['cantidad_disponible'] < $cantidad) {
            throw new Exception("Stock insuficiente para el material ID: $poa_id");
        }
        
        $precio_actual = $chk['precio_unitario'];

        // Insertar detalle
        $stmt_item->bind_param("iiid", $req_id, $poa_id, $cantidad, $precio_actual);
        
        if(!$stmt_item->execute()){
             throw new Exception("Error al guardar item: " . $stmt_item->error);
        }
        
        // ¡OJO! Aquí NO ejecutamos ningún UPDATE. 
        // El trigger 'trg_actualizar_inventario_poa' se dispara automáticamente tras el execute() de arriba.
    }

    $conn->commit();

    // Recuperar el Folio generado por el Trigger para mostrarlo al usuario
    $folio_generado = "REQ-" . $req_id; // Fallback
    $sql_folio = "SELECT folio FROM requisiciones WHERE id = $req_id";
    $res_folio = $conn->query($sql_folio);
    if($fila_f = $res_folio->fetch_assoc()){
        $folio_generado = $fila_f['folio'];
    }
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Requisición creada correctamente', 
        'req_id' => $req_id,
        'folio' => $folio_generado 
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>