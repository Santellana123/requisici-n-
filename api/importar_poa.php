<?php
require '../includes/admin_check.php';
require '../includes/db_connect.php';

// Aumentar límites para cargas grandes
ini_set('memory_limit', '512M');
set_time_limit(300); 
header('Content-Type: application/json');

// Recibir JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['datos'])) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos válidos.']);
    exit;
}

$anio = intval($input['anio']); 
$nombre_archivo_carga = $input['nombre_archivo'];
$items = $input['datos'];
$user_id = $_SESSION['user_id'];

// Iniciamos transacción
$conn->begin_transaction();

try {
    // 1. Registrar la carga en el historial
    $stmt = $conn->prepare("INSERT INTO poa_archivos (anio_fiscal, nombre_archivo, cargado_por) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $anio, $nombre_archivo_carga, $user_id);
    $stmt->execute();
    $poa_id = $conn->insert_id;

    // --- PREPARED STATEMENTS ---
    
    // A) Verificar / Crear Área
    $stmt_check_area = $conn->prepare("SELECT id FROM areas WHERE nombre_programa = ? LIMIT 1");
    $stmt_create_area = $conn->prepare("INSERT INTO areas (nombre_programa, responsable_area) VALUES (?, ?)");
    
    // B) Verificar / Crear Usuario
    $stmt_check_user  = $conn->prepare("SELECT id FROM usuarios WHERE area_id = ? LIMIT 1");
    $stmt_create_user = $conn->prepare("INSERT INTO usuarios (area_id, nombre_usuario, password, rol, activo) VALUES (?, ?, ?, 'area_operativa', 1)"); 
    
    // C) Insertar Items
    $stmt_insert = $conn->prepare("INSERT INTO poa_items 
        (poa_archivo_id, area_id, programa, proceso, partida_presupuestal, concepto, unidad_medida, cantidad_original, cantidad_disponible, precio_unitario) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // D) (ELIMINADO DE AQUÍ EL PREPARE DEL PRESUPUESTO PARA HACERLO AL FINAL Y EVITAR ERRORES)

    // Configuración para usuarios nuevos
    $password_default = password_hash('123456', PASSWORD_DEFAULT); 

    $registrados = 0;
    $areas_cache = []; 
    $usuarios_checked_cache = []; 
    $totales_por_area = []; 

    foreach ($items as $row) {
        $nombreArea = trim($row['programa']); 
        $responsable = isset($row['responsable']) && !empty($row['responsable']) ? trim($row['responsable']) : 'Responsable por asignar';
        
        $programa_txt = trim($row['programa']);
        $proceso_txt = isset($row['proceso']) ? trim($row['proceso']) : '';

        // --- VALIDACIONES DE DATOS ---
        $cant = !empty($row['cantidad']) ? (int)$row['cantidad'] : 0;
        $prec = !empty($row['precio']) ? (float)$row['precio'] : 0.00;
        $conc = !empty($row['concepto']) ? $row['concepto'] : "Sin Descripción";
        $unid = !empty($row['unidad']) ? $row['unidad'] : "Pza";
        $part = $row['partida'];

        // Calcular subtotal
        $subtotal_linea = $cant * $prec;

        // -----------------------------
        // 1. GESTIÓN DE ÁREA
        // -----------------------------
        if (isset($areas_cache[$nombreArea])) {
            $area_id = $areas_cache[$nombreArea];
        } else {
            $stmt_check_area->bind_param("s", $nombreArea);
            $stmt_check_area->execute();
            $res = $stmt_check_area->get_result();
            
            if ($res->num_rows > 0) {
                $area = $res->fetch_assoc();
                $area_id = $area['id'];
            } else {
                $stmt_create_area->bind_param("ss", $nombreArea, $responsable);
                if ($stmt_create_area->execute()) {
                    $area_id = $conn->insert_id;
                } else {
                    continue; 
                }
            }
            $areas_cache[$nombreArea] = $area_id; 
        }

        // Acumular total para el paso final
        if (!isset($totales_por_area[$area_id])) {
            $totales_por_area[$area_id] = 0;
        }
        $totales_por_area[$area_id] += $subtotal_linea;

        // -----------------------------
        // 2. GESTIÓN AUTOMÁTICA DE USUARIO
        // -----------------------------
        if (!in_array($area_id, $usuarios_checked_cache)) {
            $stmt_check_user->bind_param("i", $area_id);
            $stmt_check_user->execute();
            $res_user = $stmt_check_user->get_result();

            if ($res_user->num_rows == 0) {
                // NO existe usuario, lo creamos.
                $nombre_usuario_nuevo = mb_substr($nombreArea, 0, 50); 
                $nombre_usuario_nuevo = preg_replace('/[^a-zA-Z0-9\s]/', '', $nombre_usuario_nuevo);

                $stmt_create_user->bind_param("iss", $area_id, $nombre_usuario_nuevo, $password_default);
                try {
                    $stmt_create_user->execute();
                } catch (Exception $e) {
                    // Ignorar error si usuario ya existe (por concurrencia o duplicado nombre)
                }
            }
            $usuarios_checked_cache[] = $area_id;
        }

        // -----------------------------
        // 3. INSERTAR ITEM
        // -----------------------------
        $stmt_insert->bind_param("iisssssiid", 
            $poa_id, 
            $area_id,
            $programa_txt,
            $proceso_txt,
            $part, 
            $conc, 
            $unid, 
            $cant, 
            $cant, 
            $prec
        );
        
        if ($stmt_insert->execute()) {
            $registrados++;
        }
    }

    // -----------------------------
    // 4. ACTUALIZAR TABLA RESUMEN (CORREGIDO)
    // -----------------------------
    // IMPORTANTE: NO incluimos 'presupuesto_disponible' en el INSERT porque es generado automáticamente
    
    $stmt_final = $conn->prepare("
        INSERT INTO presupuesto_area_anual (area_id, anio_fiscal, presupuesto_asignado) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        presupuesto_asignado = presupuesto_asignado + VALUES(presupuesto_asignado)
    ");

    foreach ($totales_por_area as $id_area => $monto_total) {
        $stmt_final->bind_param("iid", $id_area, $anio, $monto_total);
        $stmt_final->execute();
    }
    
    // Cerrar el statement final
    $stmt_final->close();

    $conn->commit();
    echo json_encode([
        'status' => 'success', 
        'message' => "Carga exitosa. Se procesaron $registrados partidas para el año $anio."
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Error en base de datos: ' . $e->getMessage()]);
}
?>